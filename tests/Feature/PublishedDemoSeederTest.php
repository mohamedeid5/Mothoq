<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\ServiceCenter;
use Database\Seeders\PublishedDemoSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PublishedDemoSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_adds_repeatable_public_demo_catalog_without_accounts_or_fake_reviews(): void
    {
        $this->withoutVite();
        $this->seed(PublishedDemoSeeder::class);
        $this->seed(PublishedDemoSeeder::class);

        $this->assertDatabaseCount('service_centers', 14);
        $this->assertDatabaseCount('opening_hours', 98);
        $this->assertDatabaseCount('services', 8);
        $this->assertDatabaseCount('car_brands', 6);
        $this->assertDatabaseCount('cities', 7);
        $this->assertDatabaseCount('governorates', 3);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('reviews', 0);
        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('center_images', 0);
        $this->assertSame(14, ServiceCenter::published()->publiclyAvailable()->count());
        $this->assertSame(0, ServiceCenter::verified()->count());
        $this->get(route('home'))->assertSee('(تجريبي)');
        $this->get(route('service-centers.show', 'demo-nasr-city-maintenance'))
            ->assertSee('وليست لمركز حقيقي')->assertSee('تغيير زيوت')->assertSee('Toyota');
    }

    public function test_preserves_existing_records_and_later_edits_to_demo_centers(): void
    {
        $existing = ServiceCenter::factory()->create();
        $existingAttributes = $existing->fresh()->getAttributes();
        $service = Service::factory()->create(['slug' => 'mechanics', 'name' => 'اسم مخصص', 'is_active' => false]);
        $this->seed(PublishedDemoSeeder::class);
        $demo = ServiceCenter::where('slug', 'demo-nasr-city-maintenance')->firstOrFail();
        $demo->update(['description' => 'تعديل محفوظ']);
        $demo->openingHours()->where('is_closed', false)->update(['opens_at' => '10:00']);
        $demo->services()->detach();

        $this->seed(PublishedDemoSeeder::class);

        $this->assertSame($existingAttributes, $existing->fresh()->getAttributes());
        $this->assertSame('اسم مخصص', $service->fresh()->name);
        $this->assertFalse($service->fresh()->is_active);
        $this->assertSame('تعديل محفوظ', $demo->fresh()->description);
        $this->assertSame(0, $demo->services()->count());
        $this->assertSame(6, $demo->openingHours()->where('opens_at', '10:00')->count());
        $this->assertSame(1, $demo->openingHours()->where('is_closed', true)->whereNull('opens_at')->count());
    }

    public function test_does_not_restore_deleted_demo_centers(): void
    {
        $this->seed(PublishedDemoSeeder::class);
        $demo = ServiceCenter::where('slug', 'demo-nasr-city-maintenance')->firstOrFail();
        $demo->delete();

        $this->seed(PublishedDemoSeeder::class);

        $this->assertSoftDeleted($demo);
        $this->assertSame(14, ServiceCenter::withTrashed()->count());
        $this->assertSame(13, ServiceCenter::count());
    }
}
