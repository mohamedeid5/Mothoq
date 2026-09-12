<?php

namespace Tests\Feature\Api\V1\Catalog;

use App\Models\CarBrand;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CarBrandIndexTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_only_active_car_brands_ordered_by_name(): void
    {
        $zulu = CarBrand::factory()->create(['name' => 'Zulu', 'slug' => 'zulu']);
        $alpha = CarBrand::factory()->create([
            'name' => 'Alpha',
            'slug' => 'alpha',
            'logo_path' => 'brands/alpha.svg',
        ]);
        CarBrand::factory()->create([
            'name' => 'Hidden',
            'slug' => 'hidden',
            'is_active' => false,
        ]);

        $response = $this->getJson(route('api.v1.car-brands.index'));

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $alpha->id)
            ->assertJsonPath('data.0.logo_path', 'brands/alpha.svg')
            ->assertJsonPath('data.1.id', $zulu->id)
            ->assertJsonMissing(['slug' => 'hidden'])
            ->assertJsonMissingPath('data.0.is_active');
    }
}
