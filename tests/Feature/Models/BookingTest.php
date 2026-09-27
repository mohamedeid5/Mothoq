<?php

namespace Tests\Feature\Models;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_exposes_customer_center_service_and_typed_state(): void
    {
        $booking = Booking::factory()->accepted()->create();

        $this->assertSame($booking->customer_id, $booking->customer->id);
        $this->assertSame($booking->service_center_id, $booking->serviceCenter->id);
        $this->assertSame($booking->service_id, $booking->service->id);
        $this->assertSame(BookingStatus::Accepted, $booking->status);
        $this->assertInstanceOf(\DateTimeInterface::class, $booking->scheduled_at);
        $this->assertInstanceOf(\DateTimeInterface::class, $booking->responded_at);
    }

    public function test_status_transitions_follow_the_booking_lifecycle(): void
    {
        $this->assertTrue(BookingStatus::Pending->canTransitionTo(BookingStatus::Accepted));
        $this->assertTrue(BookingStatus::Pending->canTransitionTo(BookingStatus::Rejected));
        $this->assertTrue(BookingStatus::Pending->canTransitionTo(BookingStatus::Cancelled));
        $this->assertTrue(BookingStatus::Accepted->canTransitionTo(BookingStatus::Completed));
        $this->assertFalse(BookingStatus::Accepted->canTransitionTo(BookingStatus::Rejected));
        $this->assertFalse(BookingStatus::Completed->canTransitionTo(BookingStatus::Pending));
    }
}
