<?php

namespace Tests\Feature;

use App\Enums\OutboxStatus;
use App\Models\OutboxMessage;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Process\Process;
use Tests\TestCase;

#[Group('mysql')]
class OutboxConcurrencyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Requires an isolated MySQL test database and access to performance_schema.data_lock_waits.');
        }
        if (! str_ends_with(DB::connection()->getDatabaseName(), '_test')) {
            $this->fail('Concurrency tests require a disposable database ending in _test.');
        }
        $this->beforeApplicationDestroyed(static function (): void {
            RefreshDatabaseState::$migrated = false;
        });
        $this->artisan('migrate:fresh', ['--force' => true, '--no-interaction' => true])->assertSuccessful();
    }

    public function test_two_workers_claiming_the_same_message_send_only_once(): void
    {
        $message = OutboxMessage::factory()->create();
        $workers = [$this->worker($message->id), $this->worker($message->id)];
        DB::beginTransaction();
        OutboxMessage::query()->whereKey($message->id)->lockForUpdate()->firstOrFail();

        try {
            foreach ($workers as $worker) {
                $worker->start();
            }
            $deadline = microtime(true) + 15;
            do {
                $waiting = (int) DB::selectOne('SELECT COUNT(*) AS waiting FROM performance_schema.data_lock_waits')->waiting;
                if ($waiting >= 2) {
                    break;
                }
                foreach ($workers as $worker) {
                    if (! $worker->isRunning()) {
                        $this->fail('Worker exited before the lock was released: '.$worker->getOutput().$worker->getErrorOutput());
                    }
                }
                usleep(10000);
            } while (microtime(true) < $deadline);
            $this->assertGreaterThanOrEqual(2, $waiting);
            DB::commit();

            $deliveries = [];
            foreach ($workers as $worker) {
                $worker->wait();
                $this->assertTrue($worker->isSuccessful(), $worker->getOutput().$worker->getErrorOutput());
                $deliveries[] = trim($worker->getOutput());
            }
            sort($deliveries);
            $this->assertSame(['0', '1'], $deliveries);
            $message->refresh();
            $this->assertSame(OutboxStatus::Sent, $message->status);
            $this->assertSame(1, $message->attempts);
        } finally {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            foreach ($workers as $worker) {
                if ($worker->isRunning()) {
                    $worker->stop();
                }
            }
        }
    }

    private function worker(int $messageId): Process
    {
        $code = <<<'WORKER'
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$app->make(App\Actions\Bookings\ProcessOutboxMessageAction::class)->handle((int) $argv[1]);
echo Illuminate\Support\Facades\Mail::mailer()->getSymfonyTransport()->messages()->count();
WORKER;
        $connection = config('database.connections.mysql');

        return new Process([PHP_BINARY, '-r', $code, (string) $messageId], base_path(), [
            'APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql', 'DB_URL' => '',
            'DB_HOST' => $connection['host'], 'DB_PORT' => (string) $connection['port'],
            'DB_DATABASE' => $connection['database'], 'DB_USERNAME' => $connection['username'],
            'DB_PASSWORD' => $connection['password'], 'DB_SOCKET' => $connection['unix_socket'],
            'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array', 'QUEUE_CONNECTION' => 'sync',
            'MAIL_MAILER' => 'array', 'LOG_CHANNEL' => 'stderr',
        ], null, 30);
    }
}
