<?php

namespace Tests\Feature\Policies;

use App\Models\CenterImage;
use App\Models\ServiceCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class CenterImagePolicyTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_manage_images_for_any_service_center(): void
    {
        $admin = User::factory()->admin()->create();
        $serviceCenter = ServiceCenter::factory()->create();
        $centerImage = CenterImage::factory()->for($serviceCenter)->create();
        $gate = Gate::forUser($admin);

        $this->assertTrue($gate->allows('create', [CenterImage::class, $serviceCenter]));
        $this->assertTrue($gate->allows('update', $centerImage));
        $this->assertTrue($gate->allows('delete', $centerImage));
    }

    public function test_center_owner_can_manage_only_their_own_service_center_images(): void
    {
        $owner = User::factory()->centerOwner()->create();
        $ownServiceCenter = ServiceCenter::factory()->for($owner, 'owner')->create();
        $otherServiceCenter = ServiceCenter::factory()->create();
        $ownImage = CenterImage::factory()->for($ownServiceCenter)->create();
        $otherImage = CenterImage::factory()->for($otherServiceCenter)->create();
        $gate = Gate::forUser($owner);

        $this->assertTrue($gate->allows('create', [CenterImage::class, $ownServiceCenter]));
        $this->assertTrue($gate->allows('update', $ownImage));
        $this->assertTrue($gate->allows('delete', $ownImage));
        $this->assertFalse($gate->allows('create', [CenterImage::class, $otherServiceCenter]));
        $this->assertFalse($gate->allows('update', $otherImage));
        $this->assertFalse($gate->allows('delete', $otherImage));
    }

    public function test_customer_cannot_manage_service_center_images(): void
    {
        $customer = User::factory()->create();
        $serviceCenter = ServiceCenter::factory()->create();
        $centerImage = CenterImage::factory()->for($serviceCenter)->create();
        $gate = Gate::forUser($customer);

        $this->assertFalse($gate->allows('create', [CenterImage::class, $serviceCenter]));
        $this->assertFalse($gate->allows('update', $centerImage));
        $this->assertFalse($gate->allows('delete', $centerImage));
    }
}
