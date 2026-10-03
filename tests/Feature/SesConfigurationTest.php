<?php

namespace Tests\Feature;

use Aws\CommandInterface;
use Aws\Result;
use GuzzleHttp\Promise\FulfilledPromise;
use Illuminate\Mail\Message;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SesConfigurationTest extends TestCase
{
    #[DataProvider('configurationSets')]
    public function test_ses_sends_with_the_configuration_set_only_when_configured(?string $configurationSet): void
    {
        $environment = Env::getRepository();
        $originalValue = $environment->get('SES_CONFIGURATION_SET');

        try {
            if ($configurationSet === null) {
                $environment->clear('SES_CONFIGURATION_SET');
            } else {
                $environment->set('SES_CONFIGURATION_SET', $configurationSet);
            }

            $services = require config_path('services.php');
        } finally {
            if ($originalValue === null) {
                $environment->clear('SES_CONFIGURATION_SET');
            } else {
                $environment->set('SES_CONFIGURATION_SET', $originalValue);
            }
        }

        config()->set('services.ses', array_merge($services['ses'], [
            'key' => 'test-key',
            'secret' => 'test-secret',
            'region' => 'eu-north-1',
        ]));
        $mailer = Mail::mailer('ses');
        $commands = [];
        $mailer->getSymfonyTransport()->ses()->getHandlerList()->setHandler(
            function (CommandInterface $command) use (&$commands): FulfilledPromise {
                $commands[] = $command;

                return new FulfilledPromise(new Result(['MessageId' => 'test-message-id']));
            }
        );

        $mailer->raw('SES configuration test.', function (Message $message): void {
            $message->from('sender@example.com')->to('recipient@example.com')->subject('SES test');
        });

        $this->assertCount(1, $commands);
        $this->assertSame('SendRawEmail', $commands[0]->getName());

        if ($configurationSet === null || $configurationSet === '') {
            $this->assertArrayNotHasKey('ConfigurationSetName', $commands[0]->toArray());
        } else {
            $this->assertSame($configurationSet, $commands[0]['ConfigurationSetName']);
        }
    }

    /**
     * @return array<string, array{?string}>
     */
    public static function configurationSets(): array
    {
        return [
            'production' => ['mothoq-production'],
            'another environment' => ['mothoq-staging'],
            'unset' => [null],
            'empty' => [''],
        ];
    }
}
