<?php

namespace Tests\Feature\Api\V1\Owner;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\ServiceCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class BookingControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_non_owner_index_returns_403(): void
    {
        $customer = User::factory()->create();

        $this->actingWithToken($customer)
            ->getJson(route('api.v1.owner.bookings.index'))
            ->assertForbidden();
    }

    public function test_owner_lists_only_bookings_for_their_centers(): void
    {
        $owner = User::factory()->centerOwner()->create();
        $center = ServiceCenter::factory()->for($owner, 'owner')->create();
        $ownBooking = Booking::factory()->for($center, 'serviceCenter')->create();
        $otherBooking = Booking::factory()->create();

        $this->actingWithToken($owner)
            ->getJson(route('api.v1.owner.bookings.index'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownBooking->id);

        $this->assertModelExists($otherBooking);
    }

    public function test_owner_accepts_then_completes_booking(): void
    {
        $owner = User::factory()->centerOwner()->create();
        $center = ServiceCenter::factory()->for($owner, 'owner')->create();
        $booking = Booking::factory()->for($center, 'serviceCenter')->create();

        $this->actingWithToken($owner)
            ->patchJson(route('api.v1.owner.bookings.update', $booking), [
                'status' => BookingStatus::Accepted->value,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', BookingStatus::Accepted->value);

        $this->actingWithToken($owner)
            ->patchJson(route('api.v1.owner.bookings.update', $booking), [
                'status' => BookingStatus::Completed->value,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', BookingStatus::Completed->value);

        $this->assertNotNull($booking->fresh()->completed_at);
    }

    public function test_owner_cannot_update_other_centers_booking_and_receives_404(): void
    {
        $owner = User::factory()->centerOwner()->create();
        $otherBooking = Booking::factory()->create();

        $this->actingWithToken($owner)
            ->patchJson(route('api.v1.owner.bookings.update', $otherBooking), [
                'status' => BookingStatus::Accepted->value,
            ])
            ->assertNotFound();
    }

    private function actingWithToken(User $user): static
    {
        return $this->withToken($user->createToken('Test')->plainTextToken);
    }
}
