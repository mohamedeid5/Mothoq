<?php

namespace Tests\Feature\Web;

use App\Enums\BookingStatus;
use App\Enums\DayOfWeek;
use App\Models\Booking;
use App\Models\OpeningHour;
use App\Models\ServiceCenter;
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

    public function test_admin_can_reschedule_pending_booking_from_management_page(): void
    {
        $this->travelTo('2026-09-24');
        $admin = User::factory()->admin()->create();
        $center = ServiceCenter::factory()->published()->create(['booking_capacity' => 1]);
        OpeningHour::factory()->for($center)->create(['day_of_week' => DayOfWeek::Friday, 'opens_at' => '09:00', 'closes_at' => '18:00']);
        $booking = Booking::factory()->for($center)->create([
            'scheduled_at' => '2026-09-25 09:00:00', 'duration_minutes' => 60,
        ]);

        $this->actingAs($admin)->get(route('admin.bookings.index'))
            ->assertOk()
            ->assertSee(route('admin.bookings.reschedule', $booking));

        $this->patch(route('admin.bookings.reschedule', $booking), ['scheduled_at' => '2026-09-25T10:00'])
            ->assertRedirect()
            ->assertSessionHas('success', 'تم تغيير موعد الحجز بنجاح.');
        $this->assertSame('2026-09-25 10:00:00', $booking->fresh()->scheduled_at->format('Y-m-d H:i:s'));
    }
}
