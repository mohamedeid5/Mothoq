<?php

namespace Tests\Feature;

use App\Enums\DayOfWeek;
use App\Models\Booking;
use App\Models\OpeningHour;
use App\Models\Service;
use App\Models\ServiceCenter;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Process\Process;
use Tests\TestCase;

#[Group('mysql')]
class BookingCapacityConcurrencyTest extends TestCase
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

    /** @return array<string, array{string, string, int, bool}> */
    public static function competingWrites(): array
    {
        return [
            'two bookings compete for last slot' => ['create', 'create', 1, false],
            'booking competes with capacity decrease' => ['create', 'capacity', 2, true],
            'cancellation shares center lock' => ['cancel', 'create', 1, true],
            'acceptance shares center lock' => ['accept', 'create', 1, true],
        ];
    }

    #[DataProvider('competingWrites')]
    public function test_competing_writes_serialize_on_center_row(string $first, string $second, int $capacity, bool $existing): void
    {
        $center = ServiceCenter::factory()->published()->create(['booking_capacity' => $capacity]);
        $service = Service::factory()->create();
        $center->services()->attach($service, ['duration_minutes' => 60]);
        OpeningHour::factory()->for($center)->create(['day_of_week' => DayOfWeek::Friday]);
        $booking = $existing ? Booking::factory()->for($center)->create([
            'scheduled_at' => '2026-09-25 10:00:00', 'duration_minutes' => 60,
        ]) : null;
        $customers = User::factory()->count(2)->create();
        $processes = [];
        DB::beginTransaction();
        ServiceCenter::query()->whereKey($center->id)->lockForUpdate()->firstOrFail();

        try {
            foreach ([$first, $second] as $index => $operation) {
                $process = $this->worker($operation, $center->id, $service->id, $customers[$index]->id, $booking?->id);
                $process->start();
                $processes[] = $process;
            }
            $deadline = microtime(true) + 15;
            do {
                $waiting = DB::selectOne('SELECT COUNT(*) AS waiting FROM performance_schema.data_lock_waits')->waiting;
                if ((int) $waiting >= 2) {
                    break;
                }
                foreach ($processes as $process) {
                    if (! $process->isRunning()) {
                        $this->fail('Worker exited before waiting for center lock: '.$process->getOutput().$process->getErrorOutput());
                    }
                }
                usleep(10000);
            } while (microtime(true) < $deadline);
            $this->assertGreaterThanOrEqual(2, (int) $waiting, 'Both independent MySQL sessions must actually wait for the center row lock.');
            DB::commit();
            $results = [];
            foreach ($processes as $process) {
                $process->wait();
                $this->assertTrue($process->isSuccessful(), $process->getOutput().$process->getErrorOutput());
                $results[] = trim($process->getOutput());
            }

            $activeCount = $center->bookings()->whereIn('status', ['pending', 'accepted'])->count();
            $this->assertLessThanOrEqual($center->fresh()->booking_capacity, $activeCount);
            if ($first === 'create') {
                sort($results);
                $this->assertSame(['ok', 'rejected'], $results);
                $this->assertSame($center->fresh()->booking_capacity, $activeCount);
            } else {
                $this->assertSame('ok', $results[0]);
                $this->assertSame($first === 'cancel' ? 'cancelled' : 'accepted', $booking->fresh()->status->value);
                if ($first === 'accept') {
                    $this->assertSame('rejected', $results[1]);
                }
            }
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

    private function worker(string $operation, int $centerId, int $serviceId, int $customerId, ?int $bookingId): Process
    {
        $code = <<<'WORKER'
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
Carbon\CarbonImmutable::setTestNow('2026-09-24');
Illuminate\Support\Facades\Mail::fake();
[$operation, $centerId, $serviceId, $customerId, $bookingId] = array_slice($argv, 1);
try {
    $center = App\Models\ServiceCenter::findOrFail($centerId);
    match ($operation) {
        'create' => app(App\Actions\Bookings\CreateBookingAction::class)->handle(
            App\Models\User::findOrFail($customerId), $center,
            new App\Data\Bookings\CreateBookingData((int) $serviceId, '01012345678', '2026-09-25T10:00', null),
        ),
        'capacity' => app(App\Actions\ServiceCenters\UpdateBookingCapacityAction::class)->handle($center, 1),
        'cancel' => app(App\Actions\Bookings\CancelBookingAction::class)->handle(App\Models\Booking::findOrFail($bookingId)),
        'accept' => app(App\Actions\Bookings\UpdateBookingStatusAction::class)->handle(
            App\Models\Booking::findOrFail($bookingId), new App\Data\Bookings\UpdateBookingStatusData(App\Enums\BookingStatus::Accepted, null),
        ),
    };
    echo 'ok';
} catch (Illuminate\Validation\ValidationException $exception) {
    if (! isset($exception->errors()['booking_capacity'])) {
        throw $exception;
    }
    echo 'rejected';
}
WORKER;
        $connection = config('database.connections.mysql');

        return new Process([PHP_BINARY, '-r', $code, $operation, (string) $centerId, (string) $serviceId, (string) $customerId, (string) $bookingId], base_path(), [
            'APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql', 'DB_URL' => '',
            'DB_HOST' => $connection['host'], 'DB_PORT' => (string) $connection['port'],
            'DB_DATABASE' => $connection['database'], 'DB_USERNAME' => $connection['username'],
            'DB_PASSWORD' => $connection['password'], 'DB_SOCKET' => $connection['unix_socket'],
            'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array', 'QUEUE_CONNECTION' => 'sync',
            'MAIL_MAILER' => 'array', 'LOG_CHANNEL' => 'stderr',
        ], null, 30);
    }
}
