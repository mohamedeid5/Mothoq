<?php

namespace Tests\Feature\Api\V1\Owner;

use App\Enums\ServiceCenterStatus;
use App\Models\City;
use App\Models\ServiceCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ServiceCenterControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_cannot_access_owner_centers(): void
    {
        $this->getJson(route('api.v1.owner.service-centers.index'))
            ->assertUnauthorized();
    }

    public function test_customer_and_admin_cannot_access_owner_routes(): void
    {
        foreach ([User::factory()->create(), User::factory()->admin()->create()] as $user) {
            $this->actingWithToken($user)
                ->getJson(route('api.v1.owner.service-centers.index'))
                ->assertForbidden();

            $this->app['auth']->forgetGuards();
        }
    }

    public function test_owner_lists_only_own_centers_including_non_published_statuses(): void
    {
        $owner = User::factory()->centerOwner()->create();
        $olderCenter = ServiceCenter::factory()->create([
            'owner_id' => $owner->id,
            'status' => ServiceCenterStatus::Draft,
        ]);
        $newerCenter = ServiceCenter::factory()->create([
            'owner_id' => $owner->id,
            'status' => ServiceCenterStatus::Suspended,
        ]);
        $otherCenter = ServiceCenter::factory()->create();

        $response = $this->actingWithToken($owner)
            ->getJson(route('api.v1.owner.service-centers.index'));

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $newerCenter->id)
            ->assertJsonPath('data.0.status', 'suspended')
            ->assertJsonPath('data.1.id', $olderCenter->id)
            ->assertJsonMissing(['id' => $otherCenter->id]);
    }

    public function test_owner_can_show_own_draft_center(): void
    {
        $owner = User::factory()->centerOwner()->create();
        $serviceCenter = ServiceCenter::factory()->create([
            'owner_id' => $owner->id,
            'status' => ServiceCenterStatus::Draft,
        ]);

        $this->actingWithToken($owner)
            ->getJson(route('api.v1.owner.service-centers.show', $serviceCenter->id))
            ->assertOk()
            ->assertJsonPath('data.id', $serviceCenter->id)
            ->assertJsonPath('data.status', 'draft');
    }

    public function test_other_owners_center_is_hidden_from_show_and_update(): void
    {
        $owner = User::factory()->centerOwner()->create();
        $otherCenter = ServiceCenter::factory()->create();

        $this->actingWithToken($owner)
            ->getJson(route('api.v1.owner.service-centers.show', $otherCenter->id))
            ->assertNotFound();

        $this->actingWithToken($owner)
            ->patchJson(route('api.v1.owner.service-centers.update', $otherCenter->id), [
                'name' => 'Unauthorized change',
            ])
            ->assertNotFound();
    }

    public function test_owner_updates_allowed_fields_without_changing_protected_fields(): void
    {
        $owner = User::factory()->centerOwner()->create();
        $otherOwner = User::factory()->centerOwner()->create();
        $newCity = City::factory()->create();
        $serviceCenter = ServiceCenter::factory()->published()->verified()->create([
            'owner_id' => $owner->id,
            'slug' => 'stable-public-url',
        ]);
        $verifiedAt = $serviceCenter->verified_at;

        $response = $this->actingWithToken($owner)->patchJson(
            route('api.v1.owner.service-centers.update', $serviceCenter->id),
            [
                'city_id' => $newCity->id,
                'name' => 'Updated Center Name',
                'description' => null,
                'phone' => '01111111111',
                'whatsapp' => null,
                'address' => 'Updated address',
                'latitude' => 29.9712,
                'longitude' => 31.1253,
                'owner_id' => $otherOwner->id,
                'slug' => 'changed-slug',
                'status' => ServiceCenterStatus::Suspended->value,
                'verified_at' => null,
            ],
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Center Name')
            ->assertJsonPath('data.city.id', $newCity->id)
            ->assertJsonPath('data.slug', 'stable-public-url')
            ->assertJsonPath('data.status', 'published')
            ->assertJsonPath('data.is_verified', true);

        $serviceCenter->refresh();
        $this->assertSame($owner->id, $serviceCenter->owner_id);
        $this->assertSame('stable-public-url', $serviceCenter->slug);
        $this->assertSame(ServiceCenterStatus::Published, $serviceCenter->status);
        $this->assertTrue($verifiedAt->equalTo($serviceCenter->verified_at));
    }

    public function test_update_validates_active_city_and_coordinate_pair(): void
    {
        $owner = User::factory()->centerOwner()->create();
        $inactiveCity = City::factory()->create(['is_active' => false]);
        $serviceCenter = ServiceCenter::factory()->create(['owner_id' => $owner->id]);

        $this->actingWithToken($owner)
            ->patchJson(route('api.v1.owner.service-centers.update', $serviceCenter->id), [
                'city_id' => $inactiveCity->id,
                'latitude' => 30.0444,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['city_id', 'longitude']);
    }

    public function test_owner_can_clear_both_coordinates_together(): void
    {
        $owner = User::factory()->centerOwner()->create();
        $serviceCenter = ServiceCenter::factory()->create([
            'owner_id' => $owner->id,
            'latitude' => 30.0444,
            'longitude' => 31.2357,
        ]);

        $this->actingWithToken($owner)
            ->patchJson(route('api.v1.owner.service-centers.update', $serviceCenter->id), [
                'latitude' => null,
                'longitude' => null,
            ])
            ->assertOk()
            ->assertJsonPath('data.coordinates', null);

        $serviceCenter->refresh();
        $this->assertNull($serviceCenter->latitude);
        $this->assertNull($serviceCenter->longitude);
    }

    private function actingWithToken(User $user): static
    {
        return $this->withToken($user->createToken('Test')->plainTextToken);
    }
}
