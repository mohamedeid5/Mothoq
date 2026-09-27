<?php

namespace Tests\Feature\Web;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AdminBookingControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_admin_lists_and_filters_all_bookings(): void
    {
        $admin = User::factory()->admin()->create();
        $pendingBooking = Booking::factory()->create(['notes' => 'طلب منتظر']);
        Booking::factory()->accepted()->create(['notes' => 'طلب مقبول']);

        $this->actingAs($admin)
            ->get(route('admin.bookings.index', ['status' => BookingStatus::Pending->value]))
            ->assertOk()
            ->assertSee('طلب منتظر')
            ->assertDontSee('طلب مقبول')
            ->assertSee(route('admin.bookings.update', $pendingBooking));
    }

    public function test_non_admin_cannot_open_admin_bookings(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->get(route('admin.bookings.index'))
            ->assertForbidden();
    }

    public function test_admin_updates_booking_for_any_center(): void
    {
        $admin = User::factory()->admin()->create();
        $booking = Booking::factory()->create();

        $this->actingAs($admin)
            ->patch(route('admin.bookings.update', $booking), [
                'status' => BookingStatus::Accepted->value,
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'تم تحديث حالة الحجز بنجاح.');

        $this->assertSame(BookingStatus::Accepted, $booking->fresh()->status);
    }
}
