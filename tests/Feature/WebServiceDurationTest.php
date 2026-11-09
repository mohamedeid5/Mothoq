<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\ServiceCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class WebServiceDurationTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_owner_can_save_duration_from_edit_page(): void
    {
        $center = ServiceCenter::factory()->published()->create();
        $service = Service::factory()->create();
        $center->services()->attach($service);
        $edit = route('owner.service-centers.edit', $center);
        $url = route('owner.service-centers.services.duration.update', [$center, $service]);

        $this->actingAs($center->owner)->get($edit)->assertOk()->assertSee('مدة الخدمات')->assertSee($url, false);
        $this->followingRedirects()->patch($url, ['duration_minutes' => 45, 'duration_service_id' => $service->id])
            ->assertOk()->assertSee('تم حفظ مدة الخدمة بنجاح.')->assertSee('value="45"', false);

        $this->assertDatabaseHas('service_service_center', ['service_center_id' => $center->id, 'service_id' => $service->id, 'duration_minutes' => 45]);
        $this->actingAs(User::factory()->create())->get(route('service-centers.show', $center->slug))
            ->assertOk()->assertSee($service->name.' — 45 دقيقة');
    }

    public function test_invalid_duration_preserves_only_submitted_service_input(): void
    {
        $center = ServiceCenter::factory()->create();
        $service = Service::factory()->create();
        $otherService = Service::factory()->create();
        $center->services()->attach($service, ['duration_minutes' => 30]);
        $center->services()->attach($otherService, ['duration_minutes' => 60]);
        $edit = route('owner.service-centers.edit', $center);

        $this->actingAs($center->owner)->from($edit)->followingRedirects()
            ->patch(route('owner.service-centers.services.duration.update', [$center, $service]), ['duration_minutes' => 0, 'duration_service_id' => $service->id])
            ->assertOk()->assertSee('value="0"', false)->assertSee('value="60"', false)->assertSee('aria-invalid="true"', false);

        $this->assertDatabaseHas('service_service_center', ['service_center_id' => $center->id, 'service_id' => $service->id, 'duration_minutes' => 30]);
    }

    public function test_owner_can_clear_duration(): void
    {
        $center = ServiceCenter::factory()->create();
        $service = Service::factory()->create();
        $center->services()->attach($service, ['duration_minutes' => 30]);

        $this->actingAs($center->owner)->patch(route('owner.service-centers.services.duration.update', [$center, $service]), ['duration_minutes' => ''])
            ->assertRedirect(route('owner.service-centers.edit', $center));

        $this->assertDatabaseHas('service_service_center', ['service_center_id' => $center->id, 'service_id' => $service->id, 'duration_minutes' => null]);
    }

    public function test_guests_and_customers_cannot_update_duration(): void
    {
        $center = ServiceCenter::factory()->create();
        $service = Service::factory()->create();
        $center->services()->attach($service, ['duration_minutes' => 30]);
        $url = route('owner.service-centers.services.duration.update', [$center, $service]);

        $this->patch($url, ['duration_minutes' => 45])->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->patch($url, ['duration_minutes' => 45])->assertForbidden();

        $this->assertDatabaseHas('service_service_center', ['service_center_id' => $center->id, 'service_id' => $service->id, 'duration_minutes' => 30]);
    }

    public function test_other_owner_cannot_update_duration(): void
    {
        $center = ServiceCenter::factory()->create();
        $service = Service::factory()->create();
        $center->services()->attach($service, ['duration_minutes' => 30]);

        $this->actingAs(User::factory()->centerOwner()->create())->patch(route('owner.service-centers.services.duration.update', [$center, $service]), ['duration_minutes' => 45])->assertNotFound();

        $this->assertDatabaseHas('service_service_center', ['service_center_id' => $center->id, 'service_id' => $service->id, 'duration_minutes' => 30]);
    }

    public function test_unattached_service_cannot_be_updated(): void
    {
        $center = ServiceCenter::factory()->create();
        $service = Service::factory()->create();

        $this->actingAs($center->owner)->patch(route('owner.service-centers.services.duration.update', [$center, $service]), ['duration_minutes' => 45])->assertNotFound();

        $this->assertDatabaseMissing('service_service_center', ['service_center_id' => $center->id, 'service_id' => $service->id]);
    }
}
