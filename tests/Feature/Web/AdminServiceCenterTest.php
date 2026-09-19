<?php

namespace Tests\Feature\Web;

use App\Enums\ServiceCenterStatus;
use App\Models\CarBrand;
use App\Models\City;
use App\Models\Service;
use App\Models\ServiceCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AdminServiceCenterTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.service-centers.index'))
            ->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_access_admin_center_management(): void
    {
        $owner = User::factory()->centerOwner()->create();

        $this->actingAs($owner)
            ->get(route('admin.service-centers.index'))
            ->assertForbidden();
    }

    public function test_admin_lists_and_filters_service_centers(): void
    {
        $admin = User::factory()->admin()->create();
        $matchingCenter = ServiceCenter::factory()->published()->verified()->create([
            'name' => 'مركز البحث المطلوب',
        ]);
        $otherCenter = ServiceCenter::factory()->create([
            'name' => 'مركز آخر',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.service-centers.index', [
                'search' => 'البحث المطلوب',
                'status' => ServiceCenterStatus::Published->value,
                'verified' => '1',
            ]))
            ->assertOk()
            ->assertSee($matchingCenter->name)
            ->assertDontSee($otherCenter->name);
    }

    public function test_admin_can_review_any_service_center(): void
    {
        $admin = User::factory()->admin()->create();
        $serviceCenter = ServiceCenter::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.service-centers.show', $serviceCenter))
            ->assertOk()
            ->assertSee($serviceCenter->name)
            ->assertSee($serviceCenter->owner->email);
    }

    public function test_admin_can_open_and_update_any_service_center(): void
    {
        $admin = User::factory()->admin()->create();
        $newCity = City::factory()->create();
        $service = Service::factory()->create();
        $carBrand = CarBrand::factory()->create();
        $serviceCenter = ServiceCenter::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.service-centers.edit', $serviceCenter))
            ->assertOk()
            ->assertSee($serviceCenter->name)
            ->assertSee($service->name)
            ->assertSee($carBrand->name)
            ->assertSee('حفظ التعديلات');

        $this->actingAs($admin)
            ->patch(route('admin.service-centers.update', $serviceCenter), [
                'name' => 'المركز بعد تعديل الأدمن',
                'city_id' => $newCity->id,
                'phone' => '01000000000',
                'address' => 'العنوان الجديد',
                'latitude' => null,
                'longitude' => null,
                'sync_services' => '1',
                'service_ids' => [$service->id],
                'sync_car_brands' => '1',
                'car_brand_ids' => [$carBrand->id],
            ])
            ->assertRedirect(route('admin.service-centers.edit', $serviceCenter))
            ->assertSessionHas('success', 'تم حفظ بيانات المركز بنجاح.');

        $this->assertDatabaseHas('service_centers', [
            'id' => $serviceCenter->id,
            'city_id' => $newCity->id,
            'name' => 'المركز بعد تعديل الأدمن',
            'phone' => '01000000000',
            'address' => 'العنوان الجديد',
        ]);
        $this->assertDatabaseHas('service_service_center', [
            'service_center_id' => $serviceCenter->id,
            'service_id' => $service->id,
        ]);
        $this->assertDatabaseHas('car_brand_service_center', [
            'service_center_id' => $serviceCenter->id,
            'car_brand_id' => $carBrand->id,
        ]);
    }

    public function test_admin_can_publish_and_suspend_a_service_center(): void
    {
        $admin = User::factory()->admin()->create();
        $serviceCenter = ServiceCenter::factory()->create();

        $this->actingAs($admin)
            ->patch(route('admin.service-centers.status.update', $serviceCenter), [
                'status' => ServiceCenterStatus::Published->value,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(ServiceCenterStatus::Published, $serviceCenter->fresh()->status);

        $this->actingAs($admin)
            ->patch(route('admin.service-centers.status.update', $serviceCenter), [
                'status' => ServiceCenterStatus::Suspended->value,
            ])
            ->assertRedirect();

        $this->assertSame(ServiceCenterStatus::Suspended, $serviceCenter->fresh()->status);
    }

    public function test_admin_can_verify_and_unverify_a_service_center(): void
    {
        $admin = User::factory()->admin()->create();
        $serviceCenter = ServiceCenter::factory()->create();

        $this->actingAs($admin)
            ->patch(route('admin.service-centers.verification.update', $serviceCenter), [
                'verified' => true,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNotNull($serviceCenter->fresh()->verified_at);

        $this->actingAs($admin)
            ->patch(route('admin.service-centers.verification.update', $serviceCenter), [
                'verified' => false,
            ])
            ->assertRedirect();

        $this->assertNull($serviceCenter->fresh()->verified_at);
    }

    public function test_status_update_rejects_an_unknown_status(): void
    {
        $admin = User::factory()->admin()->create();
        $serviceCenter = ServiceCenter::factory()->create();

        $this->actingAs($admin)
            ->from(route('admin.service-centers.show', $serviceCenter))
            ->patch(route('admin.service-centers.status.update', $serviceCenter), [
                'status' => 'unknown',
            ])
            ->assertRedirect(route('admin.service-centers.show', $serviceCenter))
            ->assertSessionHasErrors('status');

        $this->assertSame(ServiceCenterStatus::Draft, $serviceCenter->fresh()->status);
    }
}
