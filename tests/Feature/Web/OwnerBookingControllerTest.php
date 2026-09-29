<?php

namespace Tests\Feature\Web;

use App\Enums\BookingStatus;
use App\Mail\BookingStatusUpdated;
use App\Models\Booking;
use App\Models\ServiceCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OwnerBookingControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_owner_lists_only_bookings_for_their_centers(): void
    {
        $owner = User::factory()->centerOwner()->create();
        $ownCenter = ServiceCenter::factory()->for($owner, 'owner')->create();
        $ownBooking = Booking::factory()->for($ownCenter, 'serviceCenter')->create(['notes' => 'حجز المركز الخاص']);
        $otherBooking = Booking::factory()->create(['notes' => 'حجز مركز آخر']);

        $this->actingAs($owner)
            ->get(route('owner.bookings.index'))
            ->assertOk()
            ->assertSee($ownBooking->customer->name)
            ->assertSee('حجز المركز الخاص')
            ->assertDontSee('حجز مركز آخر');

        $this->assertModelExists($otherBooking);
    }

    public function test_owner_accepts_pending_booking(): void
    {
        $owner = User::factory()->centerOwner()->create();
        $center = ServiceCenter::factory()->for($owner, 'owner')->create();
        $booking = Booking::factory()->for($center, 'serviceCenter')->create();
        Mail::fake();

        $this->actingAs($owner)
            ->patch(route('owner.bookings.update', $booking), [
                'status' => BookingStatus::Accepted->value,
                'status_note' => 'الموعد متاح.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'تم تحديث حالة الحجز بنجاح.');

        $booking->refresh();
        $this->assertSame(BookingStatus::Accepted, $booking->status);
        $this->assertSame('الموعد متاح.', $booking->status_note);
        $this->assertNotNull($booking->responded_at);

        Mail::assertQueued(
            BookingStatusUpdated::class,
            fn (BookingStatusUpdated $mail): bool => $mail->hasTo($booking->customer->email)
                && $mail->booking->status === BookingStatus::Accepted
                && $mail->queue === 'emails',
        );
    }

    public function test_owner_rejects_pending_booking(): void
    {
        $owner = User::factory()->centerOwner()->create();
        $center = ServiceCenter::factory()->for($owner, 'owner')->create();
        $booking = Booking::factory()->for($center, 'serviceCenter')->create();

        $this->actingAs($owner)
            ->patch(route('owner.bookings.update', $booking), [
                'status' => BookingStatus::Rejected->value,
            ])
            ->assertRedirect();

        $this->assertSame(BookingStatus::Rejected, $booking->fresh()->status);
    }

    public function test_owner_completes_only_accepted_booking(): void
    {
        $owner = User::factory()->centerOwner()->create();
        $center = ServiceCenter::factory()->for($owner, 'owner')->create();
        $acceptedBooking = Booking::factory()->accepted()->for($center, 'serviceCenter')->create();
        $pendingBooking = Booking::factory()->for($center, 'serviceCenter')->create();

        $this->actingAs($owner)
            ->patch(route('owner.bookings.update', $acceptedBooking), [
                'status' => BookingStatus::Completed->value,
            ])
            ->assertRedirect();

        $acceptedBooking->refresh();
        $this->assertSame(BookingStatus::Completed, $acceptedBooking->status);
        $this->assertNotNull($acceptedBooking->completed_at);

        $this->actingAs($owner)
            ->patch(route('owner.bookings.update', $pendingBooking), [
                'status' => BookingStatus::Completed->value,
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame(BookingStatus::Pending, $pendingBooking->fresh()->status);
    }

    public function test_owner_cannot_update_booking_for_another_center(): void
    {
        $owner = User::factory()->centerOwner()->create();
        $otherBooking = Booking::factory()->create();

        $this->actingAs($owner)
            ->patch(route('owner.bookings.update', $otherBooking), [
                'status' => BookingStatus::Accepted->value,
            ])
            ->assertNotFound();

        $this->assertSame(BookingStatus::Pending, $otherBooking->fresh()->status);
    }
}
