<?php

namespace Tests\Feature\Api\V1;

use App\Enums\DayOfWeek;
use App\Enums\ServiceCenterStatus;
use App\Models\CarBrand;
use App\Models\CenterImage;
use App\Models\City;
use App\Models\Governorate;
use App\Models\OpeningHour;
use App\Models\Review;
use App\Models\Service;
use App\Models\ServiceCenter;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ServiceCenterShowTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_a_published_center_profile_with_only_public_catalog_data(): void
    {
        $governorate = Governorate::factory()->create();
        $city = City::factory()->for($governorate)->create();
        $serviceCenter = ServiceCenter::factory()->for($city)->published()->verified()->create([
            'whatsapp' => '01000000000',
        ]);
        $activeService = Service::factory()->create();
        $inactiveService = Service::factory()->create(['is_active' => false]);
        $activeBrand = CarBrand::factory()->create();
        $inactiveBrand = CarBrand::factory()->create(['is_active' => false]);
        $serviceCenter->services()->attach([$activeService->id, $inactiveService->id]);
        $serviceCenter->carBrands()->attach([$activeBrand->id, $inactiveBrand->id]);
        $secondImage = CenterImage::factory()->for($serviceCenter)->create(['sort_order' => 2]);
        $coverImage = CenterImage::factory()->for($serviceCenter)->cover()->create(['sort_order' => 0]);
        OpeningHour::factory()->for($serviceCenter)->closed()->create(['day_of_week' => DayOfWeek::Friday]);
        OpeningHour::factory()->for($serviceCenter)->create(['day_of_week' => DayOfWeek::Saturday]);

        $publishedReviews = collect();
        foreach (range(1, 6) as $minutesAgo) {
            $publishedReviews->push(Review::factory()->for($serviceCenter)->published()->create([
                'rating' => 5,
                'published_at' => now()->subMinutes($minutesAgo),
            ]));
        }
        $pendingReview = Review::factory()->for($serviceCenter)->create();

        $response = $this->getJson(route('api.v1.service-centers.show', [
            'serviceCenter' => $serviceCenter->slug,
        ]));

        $response
            ->assertOk()
            ->assertJsonPath('data.slug', $serviceCenter->slug)
            ->assertJsonPath('data.whatsapp', '01000000000')
            ->assertJsonPath('data.services.0.slug', $activeService->slug)
            ->assertJsonCount(1, 'data.services')
            ->assertJsonPath('data.car_brands.0.slug', $activeBrand->slug)
            ->assertJsonCount(1, 'data.car_brands')
            ->assertJsonPath('data.images.0.id', $coverImage->id)
            ->assertJsonPath('data.images.1.id', $secondImage->id)
            ->assertJsonPath('data.opening_hours.0.day', DayOfWeek::Saturday->value)
            ->assertJsonPath('data.opening_hours.1.day', DayOfWeek::Friday->value)
            ->assertJsonPath('data.rating.average', 5)
            ->assertJsonPath('data.rating.count', 6)
            ->assertJsonCount(5, 'data.latest_reviews');

        $latestReviewIds = collect($response->json('data.latest_reviews'))->pluck('id')->all();

        $this->assertSame(
            $publishedReviews->take(5)->pluck('id')->all(),
            $latestReviewIds,
        );
        $this->assertNotContains($pendingReview->id, $latestReviewIds);
    }

    /**
     * @return array<string, array{ServiceCenterStatus}>
     */
    public static function nonPublishedStatuses(): array
    {
        return [
            'draft' => [ServiceCenterStatus::Draft],
            'suspended' => [ServiceCenterStatus::Suspended],
        ];
    }

    #[DataProvider('nonPublishedStatuses')]
    public function test_returns_404_for_a_center_that_is_not_published(ServiceCenterStatus $status): void
    {
        $serviceCenter = ServiceCenter::factory()->create(['status' => $status]);

        $response = $this->getJson(route('api.v1.service-centers.show', [
            'serviceCenter' => $serviceCenter->slug,
        ]));

        $response->assertNotFound();
    }

    public function test_returns_404_when_the_centers_location_is_inactive(): void
    {
        $inactiveGovernorate = Governorate::factory()->create(['is_active' => false]);
        $cityInInactiveGovernorate = City::factory()->for($inactiveGovernorate)->create();
        $centerInInactiveGovernorate = ServiceCenter::factory()->for($cityInInactiveGovernorate)->published()->create();
        $inactiveCity = City::factory()->create(['is_active' => false]);
        $centerInInactiveCity = ServiceCenter::factory()->for($inactiveCity)->published()->create();

        $this->getJson(route('api.v1.service-centers.show', [
            'serviceCenter' => $centerInInactiveGovernorate->slug,
        ]))->assertNotFound();
        $this->getJson(route('api.v1.service-centers.show', [
            'serviceCenter' => $centerInInactiveCity->slug,
        ]))->assertNotFound();
    }
}
