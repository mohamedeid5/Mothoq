<?php

namespace Tests\Feature;

use App\Enums\DayOfWeek;
use App\Models\Booking;
use App\Models\OpeningHour;
use App\Models\Service;
use App\Models\ServiceCenter;
use App\Models\ServiceCenterScheduleException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ScheduleIntegrityTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_database_rejects_duplicate_weekdays(): void
    {
        $center = ServiceCenter::factory()->create();
        OpeningHour::factory()->for($center)->create(['day_of_week' => DayOfWeek::Friday]);
        $this->expectException(QueryException::class);

        OpeningHour::factory()->for($center)->create(['day_of_week' => DayOfWeek::Friday]);
    }

    public function test_database_rejects_duplicate_exception_dates(): void
    {
        $center = ServiceCenter::factory()->create();
        ServiceCenterScheduleException::factory()->for($center)->create(['date' => '2026-09-25']);
        $this->expectException(QueryException::class);

        ServiceCenterScheduleException::factory()->for($center)->create(['date' => '2026-09-25']);
    }

    public function test_different_centers_can_share_exception_dates(): void
    {
        ServiceCenterScheduleException::factory()->count(2)->create(['date' => '2026-09-25']);

        $this->assertDatabaseCount('service_center_schedule_exceptions', 2);
    }

    /** @return array<string, array{string, array<string, mixed>}> */
    public static function invalidIntervals(): array
    {
        $cases = [];
        foreach (['opening_hours', 'service_center_schedule_exceptions'] as $table) {
            foreach ([
                'overnight' => ['opens_at' => '22:00', 'closes_at' => '02:00', 'is_closed' => false],
                'empty interval' => ['opens_at' => '09:00', 'closes_at' => '09:00', 'is_closed' => false],
                'missing opening' => ['opens_at' => null, 'closes_at' => '18:00', 'is_closed' => false],
                'missing closing' => ['opens_at' => '09:00', 'closes_at' => null, 'is_closed' => false],
                'closed with times' => ['opens_at' => '09:00', 'closes_at' => '18:00', 'is_closed' => true],
                'invalid hour' => ['opens_at' => '25:00', 'closes_at' => '27:00', 'is_closed' => false],
                'invalid text' => ['opens_at' => '09:xx', 'closes_at' => '18:00', 'is_closed' => false],
            ] as $name => $attributes) {
                $cases[$table.' '.$name] = [$table, $attributes];
            }
        }

        return $cases;
    }

    #[DataProvider('invalidIntervals')]
    public function test_database_rejects_invalid_intervals_without_application_validation(string $table, array $attributes): void
    {
        $center = ServiceCenter::factory()->create();
        $key = $table === 'opening_hours' ? ['day_of_week' => 'friday'] : ['date' => '2026-09-25'];
        $this->expectException(QueryException::class);

        DB::table($table)->insert(['service_center_id' => $center->id, ...$key, ...$attributes]);
    }

    public function test_constraints_apply_to_updates_as_well_as_inserts(): void
    {
        $exception = ServiceCenterScheduleException::factory()->create();
        $this->expectException(QueryException::class);

        DB::table('service_center_schedule_exceptions')->where('id', $exception->id)->update(['is_closed' => true]);
    }

    public function test_database_rejects_unknown_weekday(): void
    {
        $center = ServiceCenter::factory()->create();
        $this->expectException(QueryException::class);

        DB::table('opening_hours')->insert(['service_center_id' => $center->id, 'day_of_week' => 'holiday', 'opens_at' => null, 'closes_at' => null, 'is_closed' => true]);
    }

    /** @return array<string, array{int}> */
    public static function invalidDurations(): array
    {
        return ['zero' => [0], 'negative' => [-1], 'too long' => [1440]];
    }

    #[DataProvider('invalidDurations')]
    public function test_database_rejects_invalid_service_duration(int $duration): void
    {
        $center = ServiceCenter::factory()->create();
        $service = Service::factory()->create();
        $this->expectException(QueryException::class);

        $center->services()->attach($service, ['duration_minutes' => $duration]);
    }

    #[DataProvider('invalidDurations')]
    public function test_database_rejects_invalid_booking_duration(int $duration): void
    {
        $this->expectException(QueryException::class);

        Booking::factory()->create(['duration_minutes' => $duration]);
    }

    public function test_legacy_null_values_remain_valid(): void
    {
        $booking = Booking::factory()->create(['duration_minutes' => null, 'scheduled_at_timezone' => null]);

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'duration_minutes' => null, 'scheduled_at_timezone' => null]);
        $this->assertDatabaseHas('service_service_center', ['service_center_id' => $booking->service_center_id, 'service_id' => $booking->service_id, 'duration_minutes' => null]);
    }
}
