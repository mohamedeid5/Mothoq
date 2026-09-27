<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Mail\BookingStatusUpdated;
use App\Mail\NewBookingReceived;
use App\Models\Booking;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class BookingMailTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_new_booking_email_contains_booking_details(): void
    {
        $booking = Booking::factory()->create([
            'customer_phone' => '01012345678',
            'notes' => 'فحص صوت المحرك.',
        ])->load(['customer', 'serviceCenter.owner', 'service']);
        $mailable = new NewBookingReceived($booking);

        $mailable
            ->assertHasSubject("حجز جديد في {$booking->serviceCenter->name}")
            ->assertSeeInHtml($booking->serviceCenter->owner->name)
            ->assertSeeInHtml($booking->customer->name)
            ->assertSeeInHtml($booking->service->name)
            ->assertSeeInHtml('01012345678')
            ->assertSeeInHtml('فحص صوت المحرك.');
    }

    public function test_status_updated_email_contains_new_status_and_center_note(): void
    {
        $booking = Booking::factory()->accepted()->create([
            'status_note' => 'تم تأكيد الموعد.',
        ])->load(['customer', 'serviceCenter', 'service']);
        $mailable = new BookingStatusUpdated($booking);

        $mailable
            ->assertHasSubject('تحديث حالة حجزك: مقبول')
            ->assertSeeInHtml($booking->customer->name)
            ->assertSeeInHtml($booking->serviceCenter->name)
            ->assertSeeInHtml(BookingStatus::Accepted->label())
            ->assertSeeInHtml('تم تأكيد الموعد.');
    }
}
