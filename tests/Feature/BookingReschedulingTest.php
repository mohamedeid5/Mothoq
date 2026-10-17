<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\DayOfWeek;
use App\Models\Booking;
use App\Models\OpeningHour;
use App\Models\ServiceCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BookingReschedulingTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_customer_reschedules_pending_booking_without_changing_its_identity_or_other_fields(): void
    {
        $this->travelTo('2026-09-24');
        [$center, $booking] = $this->booking();

        $this->actingAs($booking->customer)->patchJson(route('api.v1.bookings.reschedule', $booking), [
            'scheduled_at' => '2026-09-25T09:30', 'status' => 'accepted', 'duration_minutes' => 1,
            'customer_id' => User::factory()->create()->id,
        ])->assertOk()->assertJsonPath('data.id', $booking->id)
            ->assertJsonPath('data.scheduled_at', '2026-09-25 09:30:00')
            ->assertJsonPath('data.status', 'pending');

        $updated = $booking->fresh();
        $this->assertSame($booking->id, $updated->id);
        $this->assertSame($booking->customer_id, $updated->customer_id);
        $this->assertSame($booking->service_id, $updated->service_id);
        $this->assertSame($center->id, $updated->service_center_id);
        $this->assertSame(60, $updated->duration_minutes);
        $this->assertSame(BookingStatus::Pending, $updated->status);
        $this->assertSame('2026-09-25 10:30:00', $updated->end_at->format('Y-m-d H:i:s'));
        $this->assertDatabaseCount('bookings', 1);
    }

    /** @return array<string, array{string, bool}> */
    public static function overlaps(): array
    {
        return [
            'full overlap' => ['11:00', false],
            'partial overlap' => ['10:30', false],
            'adjacent after' => ['12:00', true],
            'adjacent before' => ['10:00', true],
        ];
    }

    #[DataProvider('overlaps')]
    public function test_reschedule_respects_capacity_boundaries(string $time, bool $allowed): void
    {
        $this->travelTo('2026-09-24');
        [$center, $booking] = $this->booking();
        Booking::factory()->for($center)->create(['scheduled_at' => '2026-09-25 11:00:00', 'duration_minutes' => 60]);

        $response = $this->actingAs($booking->customer)->patchJson(route('api.v1.bookings.reschedule', $booking), [
            'scheduled_at' => '2026-09-25T'.$time,
        ]);

        if ($allowed) {
            $response->assertOk();
            $this->assertSame('2026-09-25 '.$time.':00', $booking->fresh()->scheduled_at->format('Y-m-d H:i:s'));
        } else {
            $response->assertUnprocessable()->assertJsonValidationErrors('booking_capacity');
            $this->assertSame('2026-09-25 09:00:00', $booking->fresh()->scheduled_at->format('Y-m-d H:i:s'));
        }
        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_closed_day_and_outside_hours_are_rejected(): void
    {
        $this->travelTo('2026-09-24');
        [$center, $booking] = $this->booking();
        $this->actingAs($booking->customer)->patchJson(route('api.v1.bookings.reschedule', $booking), [
            'scheduled_at' => '2026-09-25T17:30',
        ])->assertUnprocessable()->assertJsonValidationErrors('scheduled_at');

        $center->scheduleExceptions()->create(['date' => '2026-09-25', 'is_closed' => true]);
        $this->patchJson(route('api.v1.bookings.reschedule', $booking), [
            'scheduled_at' => '2026-09-25T12:00',
        ])->assertUnprocessable()->assertJsonValidationErrors('scheduled_at');
        $this->assertSame('2026-09-25 09:00:00', $booking->fresh()->scheduled_at->format('Y-m-d H:i:s'));
    }

    public function test_invalid_dates_and_missing_duration_are_rejected(): void
    {
        $this->travelTo('2026-09-24');
        [, $booking] = $this->booking();
        $url = route('api.v1.bookings.reschedule', $booking);
        $this->actingAs($booking->customer)->patchJson($url, ['scheduled_at' => '2026-09-23T10:00'])
            ->assertUnprocessable()->assertJsonValidationErrors('scheduled_at');
        $this->patchJson($url, ['scheduled_at' => '2026-09-25T10:00+02:00'])
            ->assertUnprocessable()->assertJsonValidationErrors('scheduled_at');
        $this->patchJson($url, ['scheduled_at' => '2026-09-31T10:00'])
            ->assertUnprocessable()->assertJsonValidationErrors('scheduled_at');
        $booking->update(['duration_minutes' => null]);
        $this->patchJson($url, ['scheduled_at' => '2026-09-25T10:00'])
            ->assertUnprocessable()->assertJsonValidationErrors('booking');
        $this->assertSame('2026-09-25 09:00:00', $booking->fresh()->scheduled_at->format('Y-m-d H:i:s'));
    }

    public function test_customer_cannot_create_same_center_start_by_rescheduling(): void
    {
        $this->travelTo('2026-09-24');
        [$center, $booking] = $this->booking();
        Booking::factory()->for($center)->for($booking->customer, 'customer')->create([
            'scheduled_at' => '2026-09-25 12:00:00', 'duration_minutes' => 60,
        ]);

        $this->actingAs($booking->customer)->patchJson(route('api.v1.bookings.reschedule', $booking), [
            'scheduled_at' => '2026-09-25T12:00',
        ])->assertUnprocessable()->assertJsonValidationErrors('scheduled_at');
        $this->assertSame('2026-09-25 09:00:00', $booking->fresh()->scheduled_at->format('Y-m-d H:i:s'));
    }

    /** @return array<string, array{BookingStatus}> */
    public static function ineligibleStatuses(): array
    {
        return [
            'accepted' => [BookingStatus::Accepted],
            'rejected' => [BookingStatus::Rejected],
            'cancelled' => [BookingStatus::Cancelled],
            'completed' => [BookingStatus::Completed],
        ];
    }

    #[DataProvider('ineligibleStatuses')]
    public function test_nonpending_bookings_cannot_be_rescheduled(BookingStatus $status): void
    {
        $this->travelTo('2026-09-24');
        [, $booking] = $this->booking();
        $booking->update(['status' => $status]);

        $this->actingAs($booking->customer)->patchJson(route('api.v1.bookings.reschedule', $booking), [
            'scheduled_at' => '2026-09-25T10:00',
        ])->assertUnprocessable()->assertJsonValidationErrors('booking');
        $this->assertSame('2026-09-25 09:00:00', $booking->fresh()->scheduled_at->format('Y-m-d H:i:s'));
    }

    public function test_authentication_ownership_owner_role_and_admin_access(): void
    {
        $this->travelTo('2026-09-24');
        [$center, $booking] = $this->booking();
        $url = route('api.v1.bookings.reschedule', $booking);
        $payload = ['scheduled_at' => '2026-09-25T10:00'];
        $this->patchJson($url, $payload)->assertUnauthorized();
        $this->actingAs(User::factory()->create())->patchJson($url, $payload)->assertNotFound();
        $this->actingAs($center->owner)->patchJson($url, $payload)->assertForbidden();
        $this->actingAs($center->owner)->patchJson(route('api.v1.admin.bookings.reschedule', $booking), $payload)->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->patchJson(route('api.v1.admin.bookings.reschedule', $booking), $payload)
            ->assertOk()->assertJsonPath('data.scheduled_at', '2026-09-25 10:00:00');
    }

    /** @return array{ServiceCenter, Booking} */
    private function booking(): array
    {
        $center = ServiceCenter::factory()->published()->create(['booking_capacity' => 1]);
        OpeningHour::factory()->for($center)->create(['day_of_week' => DayOfWeek::Friday, 'opens_at' => '09:00', 'closes_at' => '18:00']);
        $booking = Booking::factory()->for($center)->create(['scheduled_at' => '2026-09-25 09:00:00', 'duration_minutes' => 60]);

        return [$center, $booking];
    }
}
