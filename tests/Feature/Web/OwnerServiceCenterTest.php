<?php

namespace Tests\Feature\Web;

use App\Models\ServiceCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class OwnerServiceCenterTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_guest_is_redirected_to_login_from_owner_dashboard(): void
    {
        $this->get(route('owner.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_owner_dashboard_lists_only_the_authenticated_owners_centers(): void
    {
        $owner = User::factory()->centerOwner()->create();
        $ownCenter = ServiceCenter::factory()->create(['owner_id' => $owner->id]);
        $otherCenter = ServiceCenter::factory()->create();

        $this->actingAs($owner)
            ->get(route('owner.dashboard'))
            ->assertOk()
            ->assertSee($ownCenter->name)
            ->assertDontSee($otherCenter->name);
    }

    public function test_owner_cannot_open_another_owners_center(): void
    {
        $owner = User::factory()->centerOwner()->create();
        $otherCenter = ServiceCenter::factory()->create();

        $this->actingAs($owner)
            ->get(route('owner.service-centers.edit', $otherCenter))
            ->assertNotFound();
    }

    public function test_owner_updates_a_center_and_is_redirected_back_to_the_form(): void
    {
        $owner = User::factory()->centerOwner()->create();
        $serviceCenter = ServiceCenter::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($owner)
            ->patch(route('owner.service-centers.update', $serviceCenter), [
                'name' => 'الاسم الجديد للمركز',
                'city_id' => $serviceCenter->city_id,
                'phone' => $serviceCenter->phone,
                'address' => $serviceCenter->address,
                'latitude' => null,
                'longitude' => null,
            ])
            ->assertRedirect(route('owner.service-centers.edit', $serviceCenter))
            ->assertSessionHas('success', 'تم حفظ بيانات المركز بنجاح.');

        $this->assertDatabaseHas('service_centers', [
            'id' => $serviceCenter->id,
            'name' => 'الاسم الجديد للمركز',
        ]);
    }
}
