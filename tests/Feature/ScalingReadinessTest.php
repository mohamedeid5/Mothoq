<?php

namespace Tests\Feature;

use App\Models\CenterImage;
use Illuminate\Database\Connection;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class ScalingReadinessTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected array $connectionsToTransact = ['sqlite'];

    protected function setUp(): void
    {
        parent::setUp();

        $database = DB::connection();
        DB::extend('mysql', fn (): Connection => $database);

        config()->set([
            'database.default' => 'mysql',
            'database.connections.mysql.host' => 'database.example.eu-north-1.rds.amazonaws.com',
            'session.driver' => 'redis',
            'cache.default' => 'redis',
            'queue.default' => 'redis',
            'database.redis.default.host' => 'tls://shared-redis.example.com',
            'database.redis.cache.host' => 'tls://shared-redis.example.com',
            'filesystems.center_images_disk' => 's3',
            'filesystems.disks.s3.bucket' => 'shared-uploads',
        ]);
    }

    public function test_it_accepts_available_shared_state(): void
    {
        Redis::shouldReceive('connection')->withNoArgs()->once()->andReturnSelf();
        Redis::shouldReceive('connection')->with('cache')->once()->andReturnSelf();
        Redis::shouldReceive('ping')->twice()->andReturn(true);
        Storage::fake('s3');

        $this->artisan('app:check-scaling-readiness')->assertSuccessful();
    }

    public function test_it_rejects_a_local_database(): void
    {
        config()->set('database.connections.mysql.host', 'mysql');

        $this->artisan('app:check-scaling-readiness')
            ->expectsOutput('Auto Scaling requires the shared RDS MySQL database.')
            ->assertFailed();
    }

    public function test_it_rejects_local_sessions(): void
    {
        config()->set('session.driver', 'file');

        $this->artisan('app:check-scaling-readiness')->assertFailed();
    }

    public function test_it_rejects_local_cache(): void
    {
        config()->set('cache.default', 'file');

        $this->artisan('app:check-scaling-readiness')->assertFailed();
    }

    public function test_it_rejects_a_non_redis_queue(): void
    {
        config()->set('queue.default', 'sync');

        $this->artisan('app:check-scaling-readiness')->assertFailed();
    }

    public function test_it_rejects_unencrypted_redis(): void
    {
        config()->set('database.redis.default.host', 'redis');

        $this->artisan('app:check-scaling-readiness')->assertFailed();
    }

    public function test_it_rejects_unencrypted_cache_redis(): void
    {
        config()->set('database.redis.cache.host', 'redis');

        $this->artisan('app:check-scaling-readiness')->assertFailed();
    }

    public function test_it_rejects_local_upload_storage(): void
    {
        config()->set('filesystems.center_images_disk', 'public');

        $this->artisan('app:check-scaling-readiness')->assertFailed();
    }

    public function test_it_rejects_a_missing_upload_bucket(): void
    {
        config()->set('filesystems.disks.s3.bucket', null);

        $this->artisan('app:check-scaling-readiness')->assertFailed();
    }

    public function test_it_blocks_startup_when_images_still_depend_on_the_old_host(): void
    {
        CenterImage::factory()->create(['disk' => 'public']);

        $this->artisan('app:check-scaling-readiness')
            ->expectsOutput('Migrate existing center image files and disk records to S3 before enabling Auto Scaling.')
            ->assertFailed();
    }

    public function test_it_fails_without_exposing_connection_secrets(): void
    {
        Redis::shouldReceive('connection')->andThrow(new RuntimeException('secret-password'));

        $this->artisan('app:check-scaling-readiness')
            ->expectsOutput('Shared database, Redis or S3 is unavailable. Verify connectivity and instance permissions.')
            ->doesntExpectOutputToContain('secret-password')
            ->assertFailed();
    }
}
