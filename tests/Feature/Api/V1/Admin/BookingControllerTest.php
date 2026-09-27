<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class BookingControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_non_admin_index_returns_403(): void
    {
        $owner = User::factory()->centerOwner()->create();

        $this->actingWithToken($owner)
            ->getJson(route('api.v1.admin.bookings.index'))
            ->assertForbidden();
    }

    public function test_admin_lists_bookings_filtered_by_status(): void
    {
        $admin = User::factory()->admin()->create();
        $pendingBooking = Booking::factory()->create();
        Booking::factory()->accepted()->create();

        $this->actingWithToken($admin)
            ->getJson(route('api.v1.admin.bookings.index', ['status' => BookingStatus::Pending->value]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $pendingBooking->id);
    }

    public function test_admin_updates_booking_for_any_center(): void
    {
        $admin = User::factory()->admin()->create();
        $booking = Booking::factory()->create();

        $this->actingWithToken($admin)
            ->patchJson(route('api.v1.admin.bookings.update', $booking), [
                'status' => BookingStatus::Rejected->value,
                'status_note' => 'الموعد غير متاح.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', BookingStatus::Rejected->value)
            ->assertJsonPath('data.status_note', 'الموعد غير متاح.');

        $this->assertSame(BookingStatus::Rejected, $booking->fresh()->status);
    }

    private function actingWithToken(User $user): static
    {
        return $this->withToken($user->createToken('Test')->plainTextToken);
    }
}
