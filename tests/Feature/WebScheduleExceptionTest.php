<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\ServiceCenter;
use App\Models\ServiceCenterScheduleException;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WebScheduleExceptionTest extends TestCase
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
    public function test_manager_can_view_create_update_and_delete_exceptions(string $prefix): void
    {
        $center = ServiceCenter::factory()->create();
        $user = $prefix === 'admin' ? User::factory()->admin()->create() : $center->owner;
        $edit = route($prefix.'.service-centers.edit', $center);
        $store = route($prefix.'.service-centers.schedule-exceptions.store', $center);

        $this->actingAs($user)->get($edit)->assertOk()->assertSee('المواعيد الاستثنائية')->assertSee($store, false);
        $this->put($store, ['date' => '2027-01-01', 'is_closed' => '1'])->assertRedirect($edit)->assertSessionHasNoErrors();
        $exception = $center->scheduleExceptions()->sole();
        $this->assertTrue($exception->is_closed);

        $this->put($store, ['date' => '2027-01-01', 'is_closed' => '0', 'opens_at' => '10:00', 'closes_at' => '16:00'])
            ->assertRedirect($edit)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('service_center_schedule_exceptions', 1);
        $this->assertFalse($exception->fresh()->is_closed);
        $this->assertSame('10:00', substr($exception->fresh()->opens_at, 0, 5));
        $this->get($edit)->assertOk()->assertSee('2027-01-01')->assertSee('10:00');

        $this->delete(route($prefix.'.service-centers.schedule-exceptions.destroy', [$center, $exception]))->assertRedirect($edit);
        $this->assertModelMissing($exception);
    }

    public function test_owner_cannot_modify_another_center_or_delete_its_exception(): void
    {
        $center = ServiceCenter::factory()->create();
        $other = ServiceCenter::factory()->create();
        $exception = ServiceCenterScheduleException::factory()->for($other)->closed()->create();
        $this->actingAs($center->owner)
            ->put(route('owner.service-centers.schedule-exceptions.store', $other), ['date' => '2027-01-01', 'is_closed' => '1'])->assertNotFound();
        $this->delete(route('owner.service-centers.schedule-exceptions.destroy', [$center, $exception]))->assertNotFound();
        $this->assertModelExists($exception);
        $this->assertDatabaseCount('service_center_schedule_exceptions', 1);
    }

    #[DataProvider('roles')]
    public function test_guests_and_customers_cannot_change_exceptions(string $prefix): void
    {
        $center = ServiceCenter::factory()->create();
        $exception = ServiceCenterScheduleException::factory()->for($center)->closed()->create();
        $store = route($prefix.'.service-centers.schedule-exceptions.store', $center);
        $delete = route($prefix.'.service-centers.schedule-exceptions.destroy', [$center, $exception]);
        $payload = ['date' => '2027-01-01', 'is_closed' => '1'];
        $this->put($store, $payload)->assertRedirect(route('login'));
        $this->delete($delete)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->put($store, $payload)->assertForbidden();
        $this->delete($delete)->assertForbidden();
        $this->assertModelExists($exception);
        $this->assertDatabaseCount('service_center_schedule_exceptions', 1);
    }

    public function test_invalid_hours_return_errors_and_old_input_to_edit_page(): void
    {
        $center = ServiceCenter::factory()->create();
        $edit = route('owner.service-centers.edit', $center);
        $this->actingAs($center->owner)->from($edit)->followingRedirects()
            ->put(route('owner.service-centers.schedule-exceptions.store', $center), [
                'date' => '2027-01-01', 'is_closed' => '0', 'opens_at' => '16:00', 'closes_at' => '10:00',
            ])->assertOk()->assertSee('وقت الإغلاق يجب أن يكون بعد وقت الفتح')->assertSee('value="2027-01-01"', false);
        $this->assertDatabaseCount('service_center_schedule_exceptions', 0);
    }

    public function test_conflicting_change_and_deletion_show_errors_without_changing_schedule(): void
    {
        $center = ServiceCenter::factory()->create();
        $exception = ServiceCenterScheduleException::factory()->for($center)->create([
            'date' => '2027-01-01', 'is_closed' => false, 'opens_at' => '09:00', 'closes_at' => '18:00',
        ]);
        $booking = Booking::factory()->for($center)->create(['scheduled_at' => '2027-01-01 12:00:00']);
        $edit = route('owner.service-centers.edit', $center);
        $this->actingAs($center->owner)->from($edit)->followingRedirects()
            ->put(route('owner.service-centers.schedule-exceptions.store', $center), ['date' => '2027-01-01', 'is_closed' => '1'])
            ->assertOk()->assertSee('التعديل يتعارض مع حجز نشط قائم');
        $this->assertFalse($exception->fresh()->is_closed);
        $this->followingRedirects()->delete(route('owner.service-centers.schedule-exceptions.destroy', [$center, $exception]))
            ->assertOk()->assertSee('التعديل يتعارض مع حجز نشط قائم');
        $this->assertModelExists($exception);
        $this->assertSame('2027-01-01 12:00:00', $booking->fresh()->scheduled_at->format('Y-m-d H:i:s'));
    }
}
