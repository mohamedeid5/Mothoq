<?php

namespace Tests\Feature;

use App\Actions\Bookings\ValidateBookingCapacityAction;
use App\Enums\BookingStatus;
use App\Enums\DayOfWeek;
use App\Models\Booking;
use App\Models\OpeningHour;
use App\Models\Service;
use App\Models\ServiceCenter;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BookingCapacityTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @return array<string, array{int, array<int, array{string, ?int, string}>, string, int, int}> */
    public static function intervals(): array
    {
        return [
            'first booking' => [1, [], '10:00', 60, 201],
            'capacity one full' => [1, [['10:00', 60, 'pending']], '10:30', 60, 422],
            'capacity two second' => [2, [['10:00', 60, 'accepted']], '10:00', 60, 201],
            'capacity two third' => [2, [['10:00', 60, 'pending'], ['10:15', 60, 'accepted']], '10:30', 60, 422],
            'back to back' => [1, [['10:00', 60, 'accepted']], '11:00', 60, 201],
            'candidate ends at existing start' => [1, [['11:00', 60, 'pending']], '10:00', 60, 201],
            'multiple overlaps but peak fits' => [2, [['10:00', 60, 'pending'], ['11:00', 60, 'accepted']], '10:00', 120, 201],
            'peak exceeds midway' => [2, [['10:00', 90, 'pending'], ['11:00', 60, 'accepted']], '10:30', 120, 422],
            'cancelled ignored' => [1, [['10:00', 60, 'cancelled']], '10:00', 60, 201],
            'rejected ignored' => [1, [['10:00', 60, 'rejected']], '10:00', 60, 201],
            'completed ignored' => [1, [['10:00', 60, 'completed']], '10:00', 60, 201],
            'unknown duration needs review' => [2, [['09:00', null, 'pending']], '10:00', 60, 422],
            'later unknown cannot overlap' => [1, [['12:00', null, 'pending']], '10:00', 60, 201],
            'unknown inactive ignored' => [1, [['10:00', null, 'cancelled']], '10:00', 60, 201],
        ];
    }

    #[DataProvider('intervals')]
    public function test_booking_enforces_peak_across_services(int $capacity, array $existing, string $start, int $duration, int $status): void
    {
        $this->travelTo('2026-09-24');
        [$center, $service] = $this->center($capacity, $duration);
        foreach ($existing as [$time, $minutes, $state]) {
            Booking::factory()->for($center)->create([
                'scheduled_at' => '2026-09-25 '.$time, 'duration_minutes' => $minutes, 'status' => $state,
            ]);
        }
        Mail::fake();

        $response = $this->actingAs(User::factory()->create())->postJson(route('api.v1.bookings.store', $center->slug), [
            'service_id' => $service->id, 'customer_phone' => '01012345678', 'scheduled_at' => '2026-09-25T'.$start,
            'duration_minutes' => 1,
        ]);

        $response->assertStatus($status);
        $this->assertDatabaseCount('bookings', count($existing) + ($status === 201 ? 1 : 0));
        if ($status === 422) {
            $response->assertJsonValidationErrors('booking_capacity');
            Mail::assertNothingQueued();
        } else {
            $response->assertJsonPath('data.duration_minutes', $duration);
        }
    }

    public function test_unconfigured_capacity_blocks_new_bookings(): void
    {
        $this->travelTo('2026-09-24');
        [$center, $service] = $this->center(null);
        Mail::fake();

        $this->actingAs(User::factory()->create())->postJson(route('api.v1.bookings.store', $center->slug), [
            'service_id' => $service->id, 'customer_phone' => '01012345678', 'scheduled_at' => '2026-09-25T10:00',
        ])->assertUnprocessable()->assertJsonValidationErrors('booking_capacity');

        $this->assertDatabaseCount('bookings', 0);
        Mail::assertNothingQueued();
    }

    /** @return array<string, array{?int, int, bool, int}> */
    public static function capacityChanges(): array
    {
        return [
            'increase' => [2, 3, true, 200],
            'decrease to peak' => [3, 2, true, 200],
            'decrease below peak' => [3, 1, true, 422],
            'nonoverlapping fit one' => [3, 1, false, 200],
            'initial configure checks existing' => [null, 1, true, 422],
        ];
    }

    #[DataProvider('capacityChanges')]
    public function test_capacity_updates_protect_existing_active_bookings(?int $old, int $proposed, bool $overlap, int $status): void
    {
        [$center] = $this->center($old);
        Booking::factory()->for($center)->create(['scheduled_at' => '2026-09-25 10:00:00', 'duration_minutes' => 60]);
        Booking::factory()->for($center)->create(['scheduled_at' => $overlap ? '2026-09-25 10:30' : '2026-09-25 11:00', 'duration_minutes' => 60]);

        $this->actingAs($center->owner)->patchJson(route('api.v1.owner.service-centers.booking-capacity.update', $center), [
            'booking_capacity' => $proposed,
        ])->assertStatus($status);

        $this->assertSame($status === 200 ? $proposed : $old, $center->fresh()->booking_capacity);
        $this->assertDatabaseCount('bookings', 2);
    }

    /** @return array<string, array{mixed}> */
    public static function invalidCapacities(): array
    {
        return ['zero' => [0], 'negative' => [-1], 'null' => [null], 'fraction' => [1.5], 'text' => ['many'], 'overflow' => [2147483648]];
    }

    #[DataProvider('invalidCapacities')]
    public function test_invalid_capacity_returns_422(mixed $capacity): void
    {
        [$center] = $this->center(2);
        $this->actingAs($center->owner)->patchJson(route('api.v1.owner.service-centers.booking-capacity.update', $center), ['booking_capacity' => $capacity])
            ->assertUnprocessable()->assertJsonValidationErrors('booking_capacity');
        $this->assertSame(2, $center->fresh()->booking_capacity);
    }

    public function test_unknown_history_prevents_capacity_configuration(): void
    {
        [$center] = $this->center(null);
        Booking::factory()->for($center)->create(['duration_minutes' => null]);
        $this->actingAs($center->owner)->patchJson(route('api.v1.owner.service-centers.booking-capacity.update', $center), ['booking_capacity' => 3])
            ->assertUnprocessable()->assertJsonValidationErrors('booking_capacity');
        $this->assertNull($center->fresh()->booking_capacity);
    }

    public function test_capacity_endpoints_enforce_authentication_role_and_ownership(): void
    {
        [$center] = $this->center(1);
        $ownerUrl = route('api.v1.owner.service-centers.booking-capacity.update', $center);
        $adminUrl = route('api.v1.admin.service-centers.booking-capacity.update', $center);
        $this->patchJson($ownerUrl, ['booking_capacity' => 2])->assertUnauthorized();
        $this->patchJson($adminUrl, ['booking_capacity' => 2])->assertUnauthorized();
        $this->actingAs(User::factory()->create())->patchJson($ownerUrl, ['booking_capacity' => 2])->assertForbidden();
        $this->patchJson($adminUrl, ['booking_capacity' => 2])->assertForbidden();
        $this->actingAs(User::factory()->centerOwner()->create())->patchJson($ownerUrl, ['booking_capacity' => 2])->assertNotFound();
        $this->assertSame(1, $center->fresh()->booking_capacity);
        $this->actingAs(User::factory()->admin()->create())->patchJson($adminUrl, ['booking_capacity' => 2])
            ->assertOk()->assertJsonPath('data.booking_capacity', 2);
        $this->assertSame(2, $center->fresh()->booking_capacity);
    }

    public function test_cancellation_releases_capacity_and_restoration_remains_forbidden(): void
    {
        $this->travelTo('2026-09-24');
        [$center, $service] = $this->center(1);
        $booking = Booking::factory()->for($center)->create(['scheduled_at' => '2026-09-25 10:00:00', 'duration_minutes' => 60]);
        Mail::fake();
        $this->actingAs($booking->customer)->deleteJson(route('api.v1.bookings.destroy', $booking))->assertOk();
        $this->actingAs(User::factory()->create())->postJson(route('api.v1.bookings.store', $center->slug), [
            'service_id' => $service->id, 'customer_phone' => '01012345678', 'scheduled_at' => '2026-09-25T10:00',
        ])->assertCreated();
        $this->actingAs($center->owner)->patchJson(route('api.v1.owner.bookings.update', $booking), ['status' => 'accepted'])
            ->assertUnprocessable();
        $this->assertSame(BookingStatus::Cancelled, $booking->fresh()->status);
    }

    public function test_status_update_cannot_change_occupancy_fields(): void
    {
        [$center] = $this->center(1);
        $booking = Booking::factory()->for($center)->create(['scheduled_at' => '2026-09-25 10:00:00', 'duration_minutes' => 60]);
        Mail::fake();
        $this->actingAs($center->owner)->patchJson(route('api.v1.owner.bookings.update', $booking), [
            'status' => 'accepted', 'scheduled_at' => '2026-09-26 15:00', 'duration_minutes' => 1, 'service_center_id' => 999, 'service_id' => 999,
        ])->assertOk();
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'scheduled_at' => '2026-09-25 10:00:00', 'duration_minutes' => 60, 'service_center_id' => $center->id, 'service_id' => $booking->service_id, 'status' => 'accepted']);
    }

    public function test_candidate_excludes_its_old_interval(): void
    {
        [$center] = $this->center(1);
        $booking = Booking::factory()->for($center)->create(['scheduled_at' => '2026-09-25 10:00:00', 'duration_minutes' => 60]);
        DB::transaction(function () use ($center, $booking): void {
            $center = ServiceCenter::query()->whereKey($center->id)->lockForUpdate()->firstOrFail();
            app(ValidateBookingCapacityAction::class)->handle($center, 1, CarbonImmutable::parse('2026-09-25 10:30'), 60, $booking->id);
        });
        $this->assertSame('2026-09-25 10:00:00', $booking->fresh()->scheduled_at->format('Y-m-d H:i:s'));
    }

    public function test_other_dates_and_centers_do_not_consume_capacity(): void
    {
        $this->travelTo('2026-09-24');
        [$center, $service] = $this->center(1);
        Booking::factory()->for($center)->create(['scheduled_at' => '2026-09-18 10:00:00', 'duration_minutes' => 60]);
        Booking::factory()->create(['scheduled_at' => '2026-09-25 10:00:00', 'duration_minutes' => 60]);
        Mail::fake();

        $this->actingAs(User::factory()->create())->postJson(route('api.v1.bookings.store', $center->slug), [
            'service_id' => $service->id, 'customer_phone' => '01012345678', 'scheduled_at' => '2026-09-25T10:00',
        ])->assertCreated();

        $this->assertDatabaseCount('bookings', 3);
    }

    public function test_historical_unknown_duration_is_not_assumed_to_have_ended(): void
    {
        $this->travelTo('2026-09-24');
        [$center, $service] = $this->center(2);
        Booking::factory()->for($center)->create(['scheduled_at' => '2026-08-01 10:00:00', 'duration_minutes' => null]);
        Mail::fake();

        $this->actingAs(User::factory()->create())->postJson(route('api.v1.bookings.store', $center->slug), [
            'service_id' => $service->id, 'customer_phone' => '01012345678', 'scheduled_at' => '2026-09-25T10:00',
        ])->assertUnprocessable()->assertJsonValidationErrors('booking_capacity');

        $this->assertDatabaseCount('bookings', 1);
        Mail::assertNothingQueued();
    }

    public function test_stored_end_time_controls_overlap_after_midnight(): void
    {
        $this->travelTo('2026-10-28');
        [$center, $service] = $this->center(1);
        $center->openingHours()->update(['opens_at' => '00:00']);
        Booking::factory()->for($center)->create([
            'scheduled_at' => '2026-10-29 23:30:00', 'duration_minutes' => 60,
        ]);
        Mail::fake();

        $this->actingAs(User::factory()->create())->postJson(route('api.v1.bookings.store', $center->slug), [
            'service_id' => $service->id, 'customer_phone' => '01012345678', 'scheduled_at' => '2026-10-30T00:00',
        ])->assertUnprocessable()->assertJsonValidationErrors('booking_capacity');

        $this->assertDatabaseCount('bookings', 1);
        Mail::assertNothingQueued();
    }

    public function test_database_rejects_nonpositive_capacity(): void
    {
        [$center] = $this->center(1);
        $this->expectException(QueryException::class);
        DB::table('service_centers')->where('id', $center->id)->update(['booking_capacity' => 0]);
    }

    /** @return array{ServiceCenter, Service} */
    private function center(?int $capacity, int $duration = 60): array
    {
        $center = ServiceCenter::factory()->published()->create(['booking_capacity' => $capacity]);
        $service = Service::factory()->create();
        $center->services()->attach($service, ['duration_minutes' => $duration]);
        OpeningHour::factory()->for($center)->create(['day_of_week' => DayOfWeek::Friday, 'opens_at' => '09:00', 'closes_at' => '18:00']);

        return [$center, $service];
    }
}
