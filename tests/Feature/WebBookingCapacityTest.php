<?php

namespace Tests\Feature;

use App\Enums\DayOfWeek;
use App\Models\Booking;
use App\Models\OpeningHour;
use App\Models\Service;
use App\Models\ServiceCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WebBookingCapacityTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    /** @return array<string, array{string}> */
    public static function roles(): array
    {
        return ['owner' => ['owner'], 'admin' => ['admin']];
    }

    #[DataProvider('roles')]
    public function test_manager_can_configure_capacity_from_edit_page(string $prefix): void
    {
        $center = ServiceCenter::factory()->create(['booking_capacity' => null]);
        $user = $prefix === 'owner' ? $center->owner : User::factory()->admin()->create();
        $edit = route($prefix.'.service-centers.edit', $center);
        $update = route($prefix.'.service-centers.booking-capacity.update', $center);
        $this->actingAs($user)->get($edit)->assertOk()->assertSee('السعة غير محددة')->assertSee($update, false);

        $this->patch($update, ['booking_capacity' => 2])->assertRedirect($edit)->assertSessionHasNoErrors()->assertSessionHas('success', 'تم حفظ سعة الحجوزات بنجاح.');

        $this->assertSame(2, $center->fresh()->booking_capacity);
        $this->get($edit)->assertOk()->assertSee('السعة الحالية: 2');
    }

    #[DataProvider('roles')]
    public function test_conflicting_capacity_shows_error_and_preserves_existing_value(string $prefix): void
    {
        $center = ServiceCenter::factory()->create(['booking_capacity' => 2]);
        Booking::factory()->count(2)->for($center)->create(['scheduled_at' => '2027-01-01 10:00:00', 'duration_minutes' => 60]);
        $user = $prefix === 'owner' ? $center->owner : User::factory()->admin()->create();
        $edit = route($prefix.'.service-centers.edit', $center);

        $this->actingAs($user)->from($edit)->followingRedirects()->patch(route($prefix.'.service-centers.booking-capacity.update', $center), ['booking_capacity' => 1])
            ->assertOk()->assertSee('سعة المركز لا تسمح')->assertSee('value="1"', false);

        $this->assertSame(2, $center->fresh()->booking_capacity);
    }

    #[DataProvider('roles')]
    public function test_guests_and_customers_cannot_update_capacity(string $prefix): void
    {
        $center = ServiceCenter::factory()->create();
        $url = route($prefix.'.service-centers.booking-capacity.update', $center);
        $this->patch($url, ['booking_capacity' => 2])->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->patch($url, ['booking_capacity' => 2])->assertForbidden();
        $this->assertSame(3, $center->fresh()->booking_capacity);
    }

    public function test_other_owner_cannot_update_capacity(): void
    {
        $center = ServiceCenter::factory()->create();
        $this->actingAs(User::factory()->centerOwner()->create())->patch(route('owner.service-centers.booking-capacity.update', $center), ['booking_capacity' => 2])->assertNotFound();
        $this->assertSame(3, $center->fresh()->booking_capacity);
    }

    public function test_invalid_capacity_returns_error_and_preserves_input(): void
    {
        $center = ServiceCenter::factory()->create();
        $edit = route('owner.service-centers.edit', $center);
        $this->actingAs($center->owner)->from($edit)->followingRedirects()->patch(route('owner.service-centers.booking-capacity.update', $center), ['booking_capacity' => 0])
            ->assertOk()->assertSee('سعة الحجوزات يجب أن تكون بين')->assertSee('value="0"', false);
        $this->assertSame(3, $center->fresh()->booking_capacity);
    }

    public function test_customer_sees_unconfigured_center_message_instead_of_booking_form(): void
    {
        $center = ServiceCenter::factory()->published()->create(['booking_capacity' => null]);
        $this->actingAs(User::factory()->create())->get(route('service-centers.show', $center->slug))
            ->assertOk()->assertSee('الحجز غير متاح حاليًا حتى يحدد المركز سعة الحجوزات.')->assertDontSee('إرسال طلب الحجز');
    }

    public function test_customer_sees_capacity_validation_error_on_booking_form(): void
    {
        $this->travelTo('2026-09-24');
        $center = ServiceCenter::factory()->published()->create(['booking_capacity' => 1]);
        $service = Service::factory()->create();
        $center->services()->attach($service, ['duration_minutes' => 60]);
        OpeningHour::factory()->for($center)->create(['day_of_week' => DayOfWeek::Friday]);
        Booking::factory()->for($center)->create(['scheduled_at' => '2026-09-25 10:00:00', 'duration_minutes' => 60]);

        $this->actingAs(User::factory()->create())->from(route('service-centers.show', $center->slug))->followingRedirects()
            ->post(route('bookings.store', $center->slug), [
                'service_id' => $service->id, 'customer_phone' => '01012345678', 'scheduled_at' => '2026-09-25T10:00',
            ])->assertOk()->assertSee('سعة المركز لا تسمح');

        $this->assertDatabaseCount('bookings', 1);
    }
}
