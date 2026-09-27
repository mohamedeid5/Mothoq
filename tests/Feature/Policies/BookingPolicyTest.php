<?php

namespace Tests\Feature\Policies;

use App\Models\Booking;
use App\Models\ServiceCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class BookingPolicyTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_customer_can_create_for_published_center_and_manage_only_pending_own_booking(): void
    {
        $customer = User::factory()->create();
        $publishedCenter = ServiceCenter::factory()->published()->create();
        $draftCenter = ServiceCenter::factory()->create();
        $pendingBooking = Booking::factory()->for($customer, 'customer')->for($publishedCenter, 'serviceCenter')->create();
        $acceptedBooking = Booking::factory()->accepted()->for($customer, 'customer')->create();
        $otherBooking = Booking::factory()->create();
        $gate = Gate::forUser($customer);

        $this->assertTrue($gate->allows('create', [Booking::class, $publishedCenter]));
        $this->assertFalse($gate->allows('create', [Booking::class, $draftCenter]));
        $this->assertTrue($gate->allows('view', $pendingBooking));
        $this->assertTrue($gate->allows('cancel', $pendingBooking));
        $this->assertFalse($gate->allows('cancel', $acceptedBooking));
        $this->assertFalse($gate->allows('view', $otherBooking));
        $this->assertFalse($gate->allows('updateStatus', $pendingBooking));
    }

    public function test_owner_can_manage_only_bookings_for_their_centers(): void
    {
        $owner = User::factory()->centerOwner()->create();
        $ownCenter = ServiceCenter::factory()->for($owner, 'owner')->create();
        $ownBooking = Booking::factory()->for($ownCenter, 'serviceCenter')->create();
        $otherBooking = Booking::factory()->create();
        $gate = Gate::forUser($owner);

        $this->assertTrue($gate->allows('view', $ownBooking));
        $this->assertTrue($gate->allows('updateStatus', $ownBooking));
        $this->assertFalse($gate->allows('view', $otherBooking));
        $this->assertFalse($gate->allows('updateStatus', $otherBooking));
        $this->assertFalse($gate->allows('create', [Booking::class, $ownCenter]));
    }

    public function test_admin_can_view_and_manage_every_booking(): void
    {
        $admin = User::factory()->admin()->create();
        $booking = Booking::factory()->create();
        $gate = Gate::forUser($admin);

        $this->assertTrue($gate->allows('viewAny', Booking::class));
        $this->assertTrue($gate->allows('view', $booking));
        $this->assertTrue($gate->allows('updateStatus', $booking));
    }
}
