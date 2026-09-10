<?php

namespace Tests\Feature\Models;

use App\Enums\ServiceCenterStatus;
use App\Models\CarBrand;
use App\Models\CenterImage;
use App\Models\City;
use App\Models\OpeningHour;
use App\Models\Review;
use App\Models\Service;
use App\Models\ServiceCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ServiceCenterTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_exposes_the_relationships_needed_by_a_center_profile(): void
    {
        $city = City::factory()->create();
        $creator = User::factory()->admin()->create();
        $serviceCenter = ServiceCenter::factory()->for($city)->for($creator, 'creator')->create();
        $services = Service::factory()->count(2)->create();
        $carBrands = CarBrand::factory()->count(2)->create();
        $serviceCenter->services()->attach($services);
        $serviceCenter->carBrands()->attach($carBrands);
        $image = CenterImage::factory()->for($serviceCenter)->create();
        $openingHour = OpeningHour::factory()->for($serviceCenter)->create();
        $review = Review::factory()->for($serviceCenter)->create();

        $serviceCenter->load(['city', 'creator', 'services', 'carBrands', 'images', 'openingHours', 'reviews']);

        $this->assertTrue($serviceCenter->city->is($city));
        $this->assertTrue($serviceCenter->creator->is($creator));
        $this->assertCount(2, $serviceCenter->services);
        $this->assertCount(2, $serviceCenter->carBrands);
        $this->assertTrue($serviceCenter->images->contains($image));
        $this->assertTrue($serviceCenter->openingHours->contains($openingHour));
        $this->assertTrue($serviceCenter->reviews->contains($review));
    }

    public function test_published_scope_returns_only_published_centers(): void
    {
        ServiceCenter::factory()->create(['status' => ServiceCenterStatus::Draft]);
        $publishedCenter = ServiceCenter::factory()->published()->create();
        ServiceCenter::factory()->create(['status' => ServiceCenterStatus::Suspended]);

        $serviceCenters = ServiceCenter::query()->published()->get();

        $this->assertCount(1, $serviceCenters);
        $this->assertTrue($serviceCenters->first()->is($publishedCenter));
    }

    public function test_verified_scope_returns_only_centers_with_a_verification_date(): void
    {
        ServiceCenter::factory()->published()->create();
        $verifiedCenter = ServiceCenter::factory()->verified()->create();

        $serviceCenters = ServiceCenter::query()->verified()->get();

        $this->assertCount(1, $serviceCenters);
        $this->assertTrue($serviceCenters->first()->is($verifiedCenter));
        $this->assertInstanceOf(\DateTimeInterface::class, $verifiedCenter->verified_at);
    }
}
