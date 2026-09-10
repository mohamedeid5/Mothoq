<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ServiceCenterStatus;
use App\Models\CarBrand;
use App\Models\CenterImage;
use App\Models\City;
use App\Models\Governorate;
use App\Models\Review;
use App\Models\Service;
use App\Models\ServiceCenter;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ServiceCenterIndexTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_only_publicly_available_centers_with_active_catalog_data(): void
    {
        $governorate = Governorate::factory()->create();
        $city = City::factory()->for($governorate)->create();
        $visibleCenter = ServiceCenter::factory()->for($city)->published()->verified()->create();
        $activeService = Service::factory()->create();
        $inactiveService = Service::factory()->create(['is_active' => false]);
        $activeBrand = CarBrand::factory()->create();
        $inactiveBrand = CarBrand::factory()->create(['is_active' => false]);
        $visibleCenter->services()->attach([$activeService->id, $inactiveService->id]);
        $visibleCenter->carBrands()->attach([$activeBrand->id, $inactiveBrand->id]);
        CenterImage::factory()->for($visibleCenter)->cover()->create(['path' => 'centers/cover.jpg']);
        Review::factory()->for($visibleCenter)->published()->create(['rating' => 5]);
        Review::factory()->for($visibleCenter)->create(['rating' => 1]);

        ServiceCenter::factory()->for($city)->create();
        ServiceCenter::factory()->for($city)->create(['status' => ServiceCenterStatus::Suspended]);
        $inactiveCity = City::factory()->for($governorate)->create(['is_active' => false]);
        ServiceCenter::factory()->for($inactiveCity)->published()->create();

        $response = $this->getJson(route('api.v1.service-centers.index'));

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', $visibleCenter->slug)
            ->assertJsonPath('data.0.is_verified', true)
            ->assertJsonPath('data.0.city.slug', $city->slug)
            ->assertJsonPath('data.0.city.governorate.slug', $governorate->slug)
            ->assertJsonPath('data.0.services.0.slug', $activeService->slug)
            ->assertJsonCount(1, 'data.0.services')
            ->assertJsonPath('data.0.car_brands.0.slug', $activeBrand->slug)
            ->assertJsonCount(1, 'data.0.car_brands')
            ->assertJsonPath('data.0.cover_image.path', 'centers/cover.jpg')
            ->assertJsonPath('data.0.rating.average', 5)
            ->assertJsonPath('data.0.rating.count', 1)
            ->assertJsonPath('meta.per_page', 15);
    }

    public function test_filters_centers_by_location_service_brand_and_verification(): void
    {
        $governorate = Governorate::factory()->create(['slug' => 'cairo']);
        $city = City::factory()->for($governorate)->create(['slug' => 'nasr-city']);
        $otherCity = City::factory()->for($governorate)->create(['slug' => 'heliopolis']);
        $service = Service::factory()->create(['slug' => 'mechanics']);
        $brand = CarBrand::factory()->create(['slug' => 'toyota']);
        $matchingCenter = ServiceCenter::factory()->for($city)->published()->verified()->create();
        $matchingCenter->services()->attach($service);
        $matchingCenter->carBrands()->attach($brand);

        $wrongCityCenter = ServiceCenter::factory()->for($otherCity)->published()->verified()->create();
        $wrongCityCenter->services()->attach($service);
        $wrongCityCenter->carBrands()->attach($brand);
        $unverifiedCenter = ServiceCenter::factory()->for($city)->published()->create();
        $unverifiedCenter->services()->attach($service);
        $unverifiedCenter->carBrands()->attach($brand);

        $response = $this->getJson(route('api.v1.service-centers.index', [
            'governorate' => 'cairo',
            'city' => 'nasr-city',
            'service' => 'mechanics',
            'car_brand' => 'toyota',
            'verified' => true,
        ]));

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', $matchingCenter->slug);
    }

    public function test_filters_unverified_centers_when_verified_is_false(): void
    {
        ServiceCenter::factory()->published()->verified()->create();
        $unverifiedCenter = ServiceCenter::factory()->published()->create();

        $response = $this->getJson(route('api.v1.service-centers.index', ['verified' => '0']));

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', $unverifiedCenter->slug);
    }

    public function test_city_filter_requires_its_governorate(): void
    {
        $city = City::factory()->create(['slug' => 'nasr-city']);

        $response = $this->getJson(route('api.v1.service-centers.index', ['city' => $city->slug]));

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('governorate')
            ->assertJsonPath('errors.governorate.0', 'يجب تحديد المحافظة عند اختيار المدينة.');
    }

    public function test_city_must_belong_to_the_selected_governorate(): void
    {
        Governorate::factory()->create(['slug' => 'cairo']);
        $giza = Governorate::factory()->create(['slug' => 'giza']);
        City::factory()->for($giza)->create(['slug' => 'dokki']);

        $response = $this->getJson(route('api.v1.service-centers.index', [
            'governorate' => 'cairo',
            'city' => 'dokki',
        ]));

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('city')
            ->assertJsonPath('errors.city.0', 'المدينة المحددة لا تتبع المحافظة المختارة.');
    }

    public function test_rejects_unavailable_catalog_filters_and_invalid_pagination(): void
    {
        $response = $this->getJson(route('api.v1.service-centers.index', [
            'service' => 'missing-service',
            'car_brand' => 'missing-brand',
            'verified' => 'sometimes',
            'per_page' => 51,
        ]));

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['service', 'car_brand', 'verified', 'per_page'])
            ->assertJsonPath('errors.service.0', 'الخدمة المحددة غير متاحة.')
            ->assertJsonPath('errors.car_brand.0', 'ماركة السيارة المحددة غير متاحة.')
            ->assertJsonPath('errors.verified.0', 'قيمة التوثيق يجب أن تكون صحيحة أو خاطئة.')
            ->assertJsonPath('errors.per_page.0', 'عدد النتائج في الصفحة يجب أن يكون بين 1 و50.');
    }

    public function test_paginates_centers_in_a_stable_order(): void
    {
        ServiceCenter::factory()->count(3)->published()->create();

        $firstPage = $this->getJson(route('api.v1.service-centers.index', ['per_page' => 2]));
        $secondPage = $this->getJson(route('api.v1.service-centers.index', ['per_page' => 2, 'page' => 2]));

        $firstPage
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 2);
        $secondPage
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.current_page', 2);

        $firstPageIds = collect($firstPage->json('data'))->pluck('id');
        $secondPageIds = collect($secondPage->json('data'))->pluck('id');

        $this->assertCount(3, $firstPageIds->merge($secondPageIds)->unique());
    }
}
