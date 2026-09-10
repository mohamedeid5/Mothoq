<?php

namespace Tests\Feature\Policies;

use App\Models\ServiceCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ServiceCenterPolicyTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_manage_any_service_center(): void
    {
        $admin = User::factory()->admin()->create();
        $serviceCenter = ServiceCenter::factory()->create();
        $gate = Gate::forUser($admin);

        $this->assertTrue($gate->allows('create', ServiceCenter::class));
        $this->assertTrue($gate->allows('update', $serviceCenter));
        $this->assertTrue($gate->allows('delete', $serviceCenter));
        $this->assertTrue($gate->allows('publish', $serviceCenter));
        $this->assertTrue($gate->allows('verify', $serviceCenter));
        $this->assertTrue($gate->allows('transferOwnership', $serviceCenter));
    }

    public function test_center_owner_can_update_only_their_own_service_center(): void
    {
        $owner = User::factory()->centerOwner()->create();
        $ownServiceCenter = ServiceCenter::factory()->for($owner, 'owner')->create();
        $otherServiceCenter = ServiceCenter::factory()->create();
        $gate = Gate::forUser($owner);

        $this->assertTrue($gate->allows('view', $ownServiceCenter));
        $this->assertTrue($gate->allows('update', $ownServiceCenter));
        $this->assertFalse($gate->allows('update', $otherServiceCenter));
        $this->assertFalse($gate->allows('delete', $ownServiceCenter));
        $this->assertFalse($gate->allows('create', ServiceCenter::class));
        $this->assertFalse($gate->allows('publish', $ownServiceCenter));
        $this->assertFalse($gate->allows('verify', $ownServiceCenter));
        $this->assertFalse($gate->allows('transferOwnership', $ownServiceCenter));
    }

    public function test_customer_cannot_manage_a_service_center(): void
    {
        $customer = User::factory()->create();
        $serviceCenter = ServiceCenter::factory()->create();
        $gate = Gate::forUser($customer);

        $this->assertFalse($gate->allows('update', $serviceCenter));
        $this->assertFalse($gate->allows('delete', $serviceCenter));
        $this->assertFalse($gate->allows('create', ServiceCenter::class));
    }
}
