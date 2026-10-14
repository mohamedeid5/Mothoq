<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class AutoscalingDeploymentTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().'/mothoq-deployment-'.bin2hex(random_bytes(8));
        mkdir($this->directory, 0700);
        file_put_contents($this->directory.'/sleep', "#!/bin/sh\nexit 0\n");
        file_put_contents($this->directory.'/aws', <<<'BASH'
#!/usr/bin/env bash
set -eu
printf '%s %s\n' "$1" "$2" >> "$TEST_OUTPUT/calls"
case "$1 $2" in
  'autoscaling describe-auto-scaling-groups')
    jq -n --arg version "$TEMPLATE_VERSION" '{AutoScalingGroups:[{LaunchTemplate:{LaunchTemplateId:"lt-test",Version:$version},Instances:[{InstanceId:"i-test",HealthStatus:"Healthy",LifecycleState:"InService"}]}]}' ;;
  'autoscaling describe-instance-refreshes')
    if [[ "$*" == *--instance-refresh-ids* ]]; then printf '%s\n' "$REFRESH_STATUS"; else printf '%s' "$ACTIVE_REFRESH"; fi ;;
  'ssm send-command')
    while [[ "$1" != --parameters ]]; do shift; done
    cp "${2#file://}" "$TEST_OUTPUT/migration.json"
    echo command-test ;;
  'ssm get-command-invocation') echo "$MIGRATION_STATUS" ;;
  'ec2 describe-launch-template-versions')
    echo '{"LaunchTemplateVersions":[{"VersionNumber":7,"LaunchTemplateData":{"TagSpecifications":[{"ResourceType":"instance","Tags":[{"Key":"Application","Value":"mothoq"},{"Key":"Release","Value":"old"}]}]}}]}' ;;
  'ec2 create-launch-template-version')
    while [[ "$1" != --launch-template-data ]]; do shift; done
    cp "${2#file://}" "$TEST_OUTPUT/release.json"
    echo 8 ;;
  'autoscaling start-instance-refresh')
    while [[ "$1" != --desired-configuration ]]; do shift; done
    cp "${2#file://}" "$TEST_OUTPUT/desired.json"
    while [[ "$1" != --preferences ]]; do shift; done
    printf '%s' "$2" > "$TEST_OUTPUT/preferences.json"
    echo refresh-test ;;
  *) echo 'Unexpected AWS operation' >&2; exit 1 ;;
esac
BASH);
        chmod($this->directory.'/aws', 0700);
        chmod($this->directory.'/sleep', 0700);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);
        parent::tearDown();
    }

    public function test_it_migrates_before_rolling_out_a_pinned_release_with_rollback(): void
    {
        $process = $this->deploy();

        $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
        $this->assertSame([
            'LaunchTemplate' => ['LaunchTemplateId' => 'lt-test', 'Version' => '8'],
        ], json_decode(file_get_contents($this->directory.'/desired.json'), true));
        $release = json_decode(file_get_contents($this->directory.'/release.json'), true);
        $this->assertSame([
            ['Key' => 'Application', 'Value' => 'mothoq'],
            ['Key' => 'Release', 'Value' => str_repeat('a', 40)],
        ], $release['TagSpecifications'][0]['Tags']);
        $preferences = json_decode(file_get_contents($this->directory.'/preferences.json'), true);
        $this->assertTrue($preferences['AutoRollback']);
        $this->assertSame(100, $preferences['MinHealthyPercentage']);
        $calls = file_get_contents($this->directory.'/calls');
        $this->assertLessThan(strpos($calls, 'autoscaling start-instance-refresh'), strpos($calls, 'ssm get-command-invocation'));
        $migration = json_decode(file_get_contents($this->directory.'/migration.json'), true);
        $this->assertStringContainsString('migrate --force --isolated=1', $migration['commands'][0]);
    }

    public function test_a_failed_migration_does_not_replace_instances(): void
    {
        $process = $this->deploy(['MIGRATION_STATUS' => 'Failed']);

        $this->assertSame(1, $process->getExitCode());
        $this->assertFileDoesNotExist($this->directory.'/desired.json');
        $this->assertFileDoesNotExist($this->directory.'/release.json');
    }

    public function test_a_rollback_is_reported_as_a_failed_deployment(): void
    {
        $process = $this->deploy(['REFRESH_STATUS' => 'RollbackSuccessful']);

        $this->assertSame(1, $process->getExitCode());
        $this->assertStringContainsString('RollbackSuccessful', $process->getErrorOutput());
    }

    public function test_an_active_refresh_blocks_migrations_and_another_rollout(): void
    {
        $process = $this->deploy(['ACTIVE_REFRESH' => 'refresh-existing']);

        $this->assertSame(1, $process->getExitCode());
        $this->assertFileDoesNotExist($this->directory.'/migration.json');
    }

    public function test_a_mutable_template_version_is_rejected_before_deployment(): void
    {
        $process = $this->deploy(['TEMPLATE_VERSION' => '$Latest']);

        $this->assertSame(1, $process->getExitCode());
        $this->assertFileDoesNotExist($this->directory.'/migration.json');
    }

    /**
     * @param  array<string, string>  $environment
     */
    private function deploy(array $environment = []): Process
    {
        $process = new Process([
            'bash', base_path('docker/deploy-autoscaling.sh'), 'eu-north-1', 'mothoq-production-web', str_repeat('a', 40),
        ], base_path(), array_merge([
            'PATH' => $this->directory.PATH_SEPARATOR.getenv('PATH'),
            'TEST_OUTPUT' => $this->directory,
            'MIGRATION_STATUS' => 'Success',
            'REFRESH_STATUS' => 'Successful',
            'ACTIVE_REFRESH' => '',
            'TEMPLATE_VERSION' => '4',
        ], $environment), null, 15);
        $process->run();

        return $process;
    }
}
