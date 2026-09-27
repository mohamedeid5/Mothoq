<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class HorizonAccessTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('horizon.index'))
            ->assertRedirect(route('login'));
    }

    public function test_customer_cannot_access_horizon(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->get(route('horizon.index'))
            ->assertForbidden();
    }

    public function test_owner_cannot_access_horizon(): void
    {
        $owner = User::factory()->centerOwner()->create();

        $this->actingAs($owner)
            ->get(route('horizon.index'))
            ->assertForbidden();
    }

    public function test_admin_can_access_horizon(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('horizon.index'))
            ->assertOk()
            ->assertSee('Horizon');
    }
}
