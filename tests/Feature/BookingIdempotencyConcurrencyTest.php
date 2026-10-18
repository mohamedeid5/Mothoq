<?php

namespace Tests\Feature;

use App\Enums\DayOfWeek;
use App\Models\OpeningHour;
use App\Models\Service;
use App\Models\ServiceCenter;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Process\Process;
use Tests\TestCase;

#[Group('mysql')]
class BookingIdempotencyConcurrencyTest extends TestCase
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
        $this->artisan('migrate:fresh', ['--force' => true, '--no-interaction' => true])->assertSuccessful();
    }

    /** @return array<string, array{bool}> */
    public static function competingKeys(): array
    {
        return [
            'same key returns the same booking' => [true],
            'different keys still obey center capacity' => [false],
        ];
    }

    #[DataProvider('competingKeys')]
    public function test_parallel_requests_do_not_create_extra_bookings(bool $sameKey): void
    {
        $center = ServiceCenter::factory()->published()->create(['booking_capacity' => 1]);
        $service = Service::factory()->create();
        $center->services()->attach($service, ['duration_minutes' => 60]);
        OpeningHour::factory()->for($center)->create(['day_of_week' => DayOfWeek::Friday]);
        $customer = User::factory()->create();
        $firstKey = (string) Str::uuid();
        $secondKey = $sameKey ? $firstKey : (string) Str::uuid();
        $processes = [];

        DB::beginTransaction();
        ServiceCenter::query()->whereKey($center->id)->lockForUpdate()->firstOrFail();

        try {
            $processes[] = $this->worker($center->id, $service->id, $customer->id, $firstKey);
            $processes[] = $this->worker($center->id, $service->id, $customer->id, $secondKey);
            foreach ($processes as $process) {
                $process->start();
            }

            $deadline = microtime(true) + 15;
            do {
                $waiting = DB::selectOne('SELECT COUNT(*) AS waiting FROM performance_schema.data_lock_waits')->waiting;
                if ((int) $waiting >= 2) {
                    break;
                }
                foreach ($processes as $process) {
                    if (! $process->isRunning()) {
                        $this->fail('Worker exited before the lock was released: '.$process->getOutput().$process->getErrorOutput());
                    }
                }
                usleep(10000);
            } while (microtime(true) < $deadline);
            $this->assertGreaterThanOrEqual(2, (int) $waiting);
            DB::commit();

            $results = [];
            foreach ($processes as $process) {
                $process->wait();
                $this->assertTrue($process->isSuccessful(), $process->getOutput().$process->getErrorOutput());
                $results[] = trim($process->getOutput());
            }

            if ($sameKey) {
                $this->assertCount(1, array_unique($results));
                $this->assertStringStartsWith('ok:', $results[0]);
            } else {
                $this->assertCount(1, array_filter($results, fn (string $result): bool => $result === 'rejected'));
                $this->assertCount(1, array_filter($results, fn (string $result): bool => str_starts_with($result, 'ok:')));
            }
            $this->assertDatabaseCount('bookings', 1);
            $this->assertDatabaseCount('idempotency_keys', 1);
        } finally {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            foreach ($processes as $process) {
                if ($process->isRunning()) {
                    $process->stop();
                }
            }
        }
    }

    private function worker(int $centerId, int $serviceId, int $customerId, string $key): Process
    {
        $code = <<<'WORKER'
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
Carbon\CarbonImmutable::setTestNow('2026-09-24');
Illuminate\Support\Facades\Mail::fake();
[$centerId, $serviceId, $customerId, $key] = array_slice($argv, 1);
$customer = App\Models\User::findOrFail($customerId);
Illuminate\Support\Facades\Auth::login($customer);
$app->instance('request', Illuminate\Http\Request::create('/api/v1/bookings', 'POST'));
try {
    $response = app(App\Actions\Bookings\CreateIdempotentBookingAction::class)->handle(
        $customer,
        App\Models\ServiceCenter::findOrFail($centerId),
        new App\Data\Bookings\CreateBookingData((int) $serviceId, '01012345678', '2026-09-25T10:00', null),
        $key,
    );
    echo 'ok:'.$response->getData(true)['data']['id'];
} catch (Illuminate\Validation\ValidationException $exception) {
    echo 'rejected';
}
WORKER;
        $connection = config('database.connections.mysql');

        return new Process([PHP_BINARY, '-r', $code, (string) $centerId, (string) $serviceId, (string) $customerId, $key], base_path(), [
            'APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql', 'DB_URL' => '',
            'DB_HOST' => $connection['host'], 'DB_PORT' => (string) $connection['port'],
            'DB_DATABASE' => $connection['database'], 'DB_USERNAME' => $connection['username'],
            'DB_PASSWORD' => $connection['password'], 'DB_SOCKET' => $connection['unix_socket'],
            'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array', 'QUEUE_CONNECTION' => 'sync',
            'MAIL_MAILER' => 'array', 'LOG_CHANNEL' => 'stderr',
        ], null, 30);
    }
}
