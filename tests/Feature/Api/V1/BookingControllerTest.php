<?php

namespace Tests\Feature\Api\V1;

use App\Enums\BookingStatus;
use App\Enums\DayOfWeek;
use App\Models\Booking;
use App\Models\OpeningHour;
use App\Models\Service;
use App\Models\ServiceCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class BookingControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_unauthenticated_index_returns_401(): void
    {
        $this->getJson(route('api.v1.bookings.index'))
            ->assertUnauthorized();
    }

    public function test_customer_creates_pending_booking_and_receives_201(): void
    {
        $this->travelTo('2026-09-24 10:00:00');
        $customer = User::factory()->create();
        $service = Service::factory()->create();
        $center = ServiceCenter::factory()->published()->create();
        $center->services()->attach($service, ['duration_minutes' => 30]);
        OpeningHour::factory()->for($center)->create(['day_of_week' => DayOfWeek::Friday]);

        $this->actingWithToken($customer)
            ->postJson(route('api.v1.bookings.store', $center->slug), [
                'service_id' => $service->id,
                'customer_phone' => '01012345678',
                'scheduled_at' => '2026-09-25T11:00',
                'notes' => 'فحص كامل',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', BookingStatus::Pending->value)
            ->assertJsonPath('data.service_center.id', $center->id)
            ->assertJsonPath('data.service.id', $service->id)
            ->assertJsonPath('data.customer.id', $customer->id);

        $this->assertDatabaseHas('bookings', [
            'customer_id' => $customer->id,
            'service_center_id' => $center->id,
            'status' => BookingStatus::Pending->value,
        ]);
    }

    public function test_store_returns_422_for_invalid_payload(): void
    {
        $customer = User::factory()->create();
        $center = ServiceCenter::factory()->published()->create();

        $this->actingWithToken($customer)
            ->postJson(route('api.v1.bookings.store', $center->slug), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['service_id', 'customer_phone', 'scheduled_at'])
            ->assertJsonPath('errors.service_id.0', 'اختر الخدمة المطلوبة.');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_owner_cannot_use_customer_booking_routes(): void
    {
        $owner = User::factory()->centerOwner()->create();

        $this->actingWithToken($owner)
            ->getJson(route('api.v1.bookings.index'))
            ->assertForbidden();
    }

    public function test_customer_lists_only_their_bookings(): void
    {
        $customer = User::factory()->create();
        $ownBooking = Booking::factory()->for($customer, 'customer')->create();
        $otherBooking = Booking::factory()->create();

        $this->actingWithToken($customer)
            ->getJson(route('api.v1.bookings.index'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownBooking->id);

        $this->assertModelExists($otherBooking);
    }

    public function test_customer_cancels_pending_booking(): void
    {
        $customer = User::factory()->create();
        $booking = Booking::factory()->for($customer, 'customer')->create();

        $this->actingWithToken($customer)
            ->deleteJson(route('api.v1.bookings.destroy', $booking))
            ->assertOk()
            ->assertJsonPath('data.status', BookingStatus::Cancelled->value);

        $this->assertSame(BookingStatus::Cancelled, $booking->fresh()->status);
    }

    public function test_customer_cannot_cancel_another_customers_booking_and_receives_404(): void
    {
        $customer = User::factory()->create();
        $otherBooking = Booking::factory()->create();

        $this->actingWithToken($customer)
            ->deleteJson(route('api.v1.bookings.destroy', $otherBooking))
            ->assertNotFound();

        $this->assertSame(BookingStatus::Pending, $otherBooking->fresh()->status);
    }

    private function actingWithToken(User $user): static
    {
        return $this->withToken($user->createToken('Test')->plainTextToken);
    }
}
