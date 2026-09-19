<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Enums\ServiceCenterStatus;
use App\Enums\UserRole;
use App\Models\CarBrand;
use App\Models\CenterImage;
use App\Models\City;
use App\Models\Service;
use App\Models\ServiceCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ServiceCenterControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_cannot_manage_service_centers(): void
    {
        $serviceCenter = ServiceCenter::factory()->create();

        $this->getJson(route('api.v1.admin.service-centers.index'))
            ->assertUnauthorized();
        $this->getJson(route('api.v1.admin.service-centers.show', $serviceCenter))
            ->assertUnauthorized();
        $this->patchJson(route('api.v1.admin.service-centers.status.update', $serviceCenter), [
            'status' => ServiceCenterStatus::Published->value,
        ])->assertUnauthorized();
        $this->patchJson(route('api.v1.admin.service-centers.verification.update', $serviceCenter), [
            'verified' => true,
        ])->assertUnauthorized();
    }

    public function test_non_admin_cannot_manage_service_centers(): void
    {
        $serviceCenter = ServiceCenter::factory()->create();

        foreach ([User::factory()->create(), User::factory()->centerOwner()->create()] as $user) {
            $this->actingWithToken($user)
                ->getJson(route('api.v1.admin.service-centers.index'))
                ->assertForbidden();

            $this->app['auth']->forgetGuards();
        }
    }

    public function test_admin_lists_and_filters_service_centers(): void
    {
        $admin = User::factory()->admin()->create();
        $matchingCenter = ServiceCenter::factory()->verified()->create([
            'name' => 'Cairo Trusted Center',
        ]);
        ServiceCenter::factory()->published()->create([
            'name' => 'Alex Center',
        ]);
        ServiceCenter::factory()->create([
            'name' => 'Cairo Draft Center',
        ]);

        $this->actingWithToken($admin)
            ->getJson(route('api.v1.admin.service-centers.index', [
                'search' => 'Cairo Trusted',
                'status' => ServiceCenterStatus::Published->value,
                'verified' => true,
            ]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingCenter->id)
            ->assertJsonPath('data.0.status', ServiceCenterStatus::Published->value)
            ->assertJsonPath('data.0.is_verified', true)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_admin_views_service_center_details(): void
    {
        $admin = User::factory()->admin()->create();
        $serviceCenter = ServiceCenter::factory()->create();
        $service = Service::factory()->create();
        $carBrand = CarBrand::factory()->create();
        $image = CenterImage::factory()->for($serviceCenter)->create();
        $serviceCenter->services()->attach($service);
        $serviceCenter->carBrands()->attach($carBrand);

        $this->actingWithToken($admin)
            ->getJson(route('api.v1.admin.service-centers.show', $serviceCenter))
            ->assertOk()
            ->assertJsonPath('data.id', $serviceCenter->id)
            ->assertJsonPath('data.services.0.id', $service->id)
            ->assertJsonPath('data.car_brands.0.id', $carBrand->id)
            ->assertJsonPath('data.images.0.id', $image->id)
            ->assertJsonStructure([
                'data' => [
                    'owner',
                    'city',
                    'services',
                    'car_brands',
                    'images',
                    'published_reviews_count',
                    'published_reviews_average',
                    'created_at',
                    'updated_at',
                ],
            ]);
    }

    public function test_admin_updates_service_center_status(): void
    {
        $admin = User::factory()->admin()->create();
        $serviceCenter = ServiceCenter::factory()->create();

        $this->actingWithToken($admin)
            ->patchJson(route('api.v1.admin.service-centers.status.update', $serviceCenter), [
                'status' => ServiceCenterStatus::Published->value,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', ServiceCenterStatus::Published->value);

        $this->assertSame(ServiceCenterStatus::Published, $serviceCenter->fresh()->status);
    }

    public function test_admin_updates_service_center_data_without_changing_separately_managed_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $otherOwner = User::factory()->centerOwner()->create();
        $newCity = City::factory()->create();
        $service = Service::factory()->create();
        $carBrand = CarBrand::factory()->create();
        $serviceCenter = ServiceCenter::factory()->verified()->create([
            'slug' => 'stable-admin-url',
        ]);
        $originalOwnerId = $serviceCenter->owner_id;
        $verifiedAt = $serviceCenter->verified_at;

        $this->actingWithToken($admin)
            ->patchJson(route('api.v1.admin.service-centers.update', $serviceCenter), [
                'city_id' => $newCity->id,
                'name' => 'Admin Updated Center',
                'description' => null,
                'phone' => '01111111111',
                'whatsapp' => null,
                'address' => 'Updated by admin',
                'latitude' => 30.0444,
                'longitude' => 31.2357,
                'service_ids' => [$service->id],
                'car_brand_ids' => [$carBrand->id],
                'owner_id' => $otherOwner->id,
                'slug' => 'attacker-slug',
                'status' => ServiceCenterStatus::Suspended->value,
                'verified_at' => null,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Admin Updated Center')
            ->assertJsonPath('data.city.id', $newCity->id)
            ->assertJsonPath('data.services.0.id', $service->id)
            ->assertJsonPath('data.car_brands.0.id', $carBrand->id)
            ->assertJsonPath('data.slug', 'stable-admin-url')
            ->assertJsonPath('data.status', ServiceCenterStatus::Published->value)
            ->assertJsonPath('data.is_verified', true);

        $serviceCenter->refresh();
        $this->assertSame($originalOwnerId, $serviceCenter->owner_id);
        $this->assertSame('stable-admin-url', $serviceCenter->slug);
        $this->assertSame(ServiceCenterStatus::Published, $serviceCenter->status);
        $this->assertTrue($verifiedAt->equalTo($serviceCenter->verified_at));
        $this->assertDatabaseHas('service_service_center', [
            'service_center_id' => $serviceCenter->id,
            'service_id' => $service->id,
        ]);
        $this->assertDatabaseHas('car_brand_service_center', [
            'service_center_id' => $serviceCenter->id,
            'car_brand_id' => $carBrand->id,
        ]);
    }

    public function test_admin_service_center_update_validates_active_city_and_coordinates(): void
    {
        $admin = User::factory()->admin()->create();
        $inactiveCity = City::factory()->create(['is_active' => false]);
        $inactiveService = Service::factory()->create(['is_active' => false]);
        $inactiveCarBrand = CarBrand::factory()->create(['is_active' => false]);
        $serviceCenter = ServiceCenter::factory()->create();

        $this->actingWithToken($admin)
            ->patchJson(route('api.v1.admin.service-centers.update', $serviceCenter), [
                'city_id' => $inactiveCity->id,
                'latitude' => 30.0444,
                'service_ids' => [$inactiveService->id],
                'car_brand_ids' => [$inactiveCarBrand->id],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'city_id',
                'longitude',
                'service_ids.0',
                'car_brand_ids.0',
            ]);
    }

    public function test_admin_updates_service_center_verification(): void
    {
        $admin = User::factory()->admin()->create();
        $serviceCenter = ServiceCenter::factory()->create();

        $this->actingWithToken($admin)
            ->patchJson(route('api.v1.admin.service-centers.verification.update', $serviceCenter), [
                'verified' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.is_verified', true);

        $this->assertNotNull($serviceCenter->fresh()->verified_at);

        $this->actingWithToken($admin)
            ->patchJson(route('api.v1.admin.service-centers.verification.update', $serviceCenter), [
                'verified' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.is_verified', false);

        $this->assertNull($serviceCenter->fresh()->verified_at);
    }

    public function test_admin_management_inputs_are_validated(): void
    {
        $admin = User::factory()->admin()->create();
        $serviceCenter = ServiceCenter::factory()->create();

        $this->actingWithToken($admin)
            ->getJson(route('api.v1.admin.service-centers.index', [
                'status' => 'unknown',
                'verified' => 'unknown',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status', 'verified']);

        $this->actingWithToken($admin)
            ->patchJson(route('api.v1.admin.service-centers.status.update', $serviceCenter), [
                'status' => 'unknown',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->actingWithToken($admin)
            ->patchJson(route('api.v1.admin.service-centers.verification.update', $serviceCenter), [
                'verified' => 'unknown',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('verified');

        $serviceCenter->refresh();
        $this->assertSame(ServiceCenterStatus::Draft, $serviceCenter->status);
        $this->assertNull($serviceCenter->verified_at);
    }

    public function test_guest_cannot_create_service_center(): void
    {
        $this->postJson(route('api.v1.admin.service-centers.store'))
            ->assertUnauthorized();
    }

    public function test_non_admin_cannot_create_service_center(): void
    {
        foreach ([User::factory()->create(), User::factory()->centerOwner()->create()] as $user) {
            $this->withToken($user->createToken('Test')->plainTextToken)
                ->postJson(route('api.v1.admin.service-centers.store'))
                ->assertForbidden();

            $this->app['auth']->forgetGuards();
        }
    }

    public function test_admin_creates_draft_center_and_promotes_customer_to_owner(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();
        $city = City::factory()->create();

        $response = $this->actingWithToken($admin)->postJson(
            route('api.v1.admin.service-centers.store'),
            $this->validPayload($owner, $city),
        );

        $response
            ->assertCreated()
            ->assertJsonPath('data.owner.id', $owner->id)
            ->assertJsonPath('data.city.id', $city->id)
            ->assertJsonPath('data.name', 'Mothoq Auto Center')
            ->assertJsonPath('data.slug', 'mothoq-auto-center')
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.is_verified', false);

        $this->assertDatabaseHas('service_centers', [
            'owner_id' => $owner->id,
            'city_id' => $city->id,
            'name' => 'Mothoq Auto Center',
            'slug' => 'mothoq-auto-center',
            'status' => ServiceCenterStatus::Draft->value,
            'verified_at' => null,
        ]);
        $this->assertSame(UserRole::CenterOwner, $owner->fresh()->role);
    }

    public function test_admin_cannot_set_protected_center_fields_during_creation(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();
        $city = City::factory()->create();

        $payload = [
            ...$this->validPayload($owner, $city),
            'slug' => 'attacker-slug',
            'status' => ServiceCenterStatus::Published->value,
            'verified_at' => now()->toISOString(),
        ];

        $response = $this->actingWithToken($admin)->postJson(
            route('api.v1.admin.service-centers.store'),
            $payload,
        );

        $response
            ->assertCreated()
            ->assertJsonPath('data.slug', 'mothoq-auto-center')
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.is_verified', false);
    }

    public function test_duplicate_names_receive_unique_slugs_including_soft_deleted_centers(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();
        $city = City::factory()->create();
        ServiceCenter::factory()->create(['slug' => 'mothoq-auto-center'])->delete();

        $response = $this->actingWithToken($admin)->postJson(
            route('api.v1.admin.service-centers.store'),
            $this->validPayload($owner, $city),
        );

        $response
            ->assertCreated()
            ->assertJsonPath('data.slug', 'mothoq-auto-center-2');
    }

    public function test_creation_validates_owner_city_and_coordinate_pair(): void
    {
        $admin = User::factory()->admin()->create();
        $invalidOwner = User::factory()->admin()->create();
        $inactiveCity = City::factory()->create(['is_active' => false]);

        $payload = $this->validPayload($invalidOwner, $inactiveCity);
        unset($payload['longitude']);

        $this->actingWithToken($admin)
            ->postJson(route('api.v1.admin.service-centers.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['owner_id', 'city_id', 'longitude']);

        $this->assertDatabaseCount('service_centers', 0);
    }

    /** @return array<string, mixed> */
    private function validPayload(User $owner, City $city): array
    {
        return [
            'owner_id' => $owner->id,
            'city_id' => $city->id,
            'name' => 'Mothoq Auto Center',
            'description' => 'Trusted maintenance center.',
            'phone' => '01012345678',
            'whatsapp' => '01012345678',
            'address' => 'Nasr City, Cairo',
            'latitude' => 30.0444,
            'longitude' => 31.2357,
        ];
    }

    private function actingWithToken(User $user): static
    {
        return $this->withToken($user->createToken('Test')->plainTextToken);
    }
}
