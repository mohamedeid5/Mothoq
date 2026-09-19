<?php

namespace Tests\Feature;

use App\Models\ServiceCenter;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class HomepageTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_homepage_renders_successfully(): void
    {
        $serviceCenter = ServiceCenter::factory()->published()->create([
            'name' => 'مركز اختبار موثوق',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertViewIs('home')
            ->assertSee('اكتشف مراكز الصيانة')
            ->assertSee($serviceCenter->name);
    }

    public function test_published_service_center_has_a_blade_profile_page(): void
    {
        $serviceCenter = ServiceCenter::factory()->published()->create();

        $this->get(route('service-centers.show', $serviceCenter->slug))
            ->assertOk()
            ->assertViewIs('service-centers.show')
            ->assertSee($serviceCenter->name);
    }

    public function test_unpublished_service_center_is_not_publicly_visible(): void
    {
        $serviceCenter = ServiceCenter::factory()->create();

        $this->get(route('service-centers.show', $serviceCenter->slug))
            ->assertNotFound();
    }
}
