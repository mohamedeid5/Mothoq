<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Enums\ServiceCenterStatus;
use App\Enums\UserRole;
use App\Models\City;
use App\Models\ServiceCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ServiceCenterControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

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
