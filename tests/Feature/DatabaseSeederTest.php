<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ServiceCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_creates_a_connected_and_repeatable_demo_catalog(): void
    {
        $this->seed();
        $this->seed();

        $admin = User::query()->where('email', 'admin@mothoq.test')->firstOrFail();
        $owner = User::query()->where('email', 'owner@mothoq.test')->firstOrFail();
        $serviceCenter = ServiceCenter::query()
            ->with(['owner', 'city.governorate', 'services', 'carBrands', 'openingHours', 'images', 'reviews'])
            ->firstOrFail();

        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertSame(UserRole::CenterOwner, $owner->role);
        $this->assertTrue($serviceCenter->owner->is($owner));
        $this->assertDatabaseCount('governorates', 3);
        $this->assertDatabaseCount('cities', 7);
        $this->assertDatabaseCount('services', 8);
        $this->assertDatabaseCount('car_brands', 6);
        $this->assertDatabaseCount('car_models', 18);
        $this->assertDatabaseCount('service_centers', 3);
        $this->assertDatabaseCount('opening_hours', 21);
        $this->assertDatabaseCount('center_images', 3);
        $this->assertDatabaseCount('reviews', 3);
        $this->assertNotNull($serviceCenter->city->governorate);
        $this->assertNotEmpty($serviceCenter->services);
        $this->assertNotEmpty($serviceCenter->carBrands);
        $this->assertCount(7, $serviceCenter->openingHours);
        $this->assertCount(1, $serviceCenter->images);
        $this->assertCount(1, $serviceCenter->reviews);
    }
}
