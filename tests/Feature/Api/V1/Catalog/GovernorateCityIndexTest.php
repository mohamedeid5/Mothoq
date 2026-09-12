<?php

namespace Tests\Feature\Api\V1\Catalog;

use App\Models\City;
use App\Models\Governorate;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class GovernorateCityIndexTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_only_active_cities_for_the_requested_active_governorate(): void
    {
        $governorate = Governorate::factory()->create(['slug' => 'cairo']);
        $otherGovernorate = Governorate::factory()->create(['slug' => 'giza']);
        $zulu = City::factory()->for($governorate)->create(['name' => 'Zulu', 'slug' => 'zulu']);
        $alpha = City::factory()->for($governorate)->create(['name' => 'Alpha', 'slug' => 'alpha']);
        City::factory()->for($governorate)->create([
            'name' => 'Hidden',
            'slug' => 'hidden',
            'is_active' => false,
        ]);
        City::factory()->for($otherGovernorate)->create(['name' => 'Other', 'slug' => 'other']);

        $response = $this->getJson(route('api.v1.governorates.cities.index', [
            'governorate' => $governorate->slug,
        ]));

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $alpha->id)
            ->assertJsonPath('data.1.id', $zulu->id)
            ->assertJsonMissing(['slug' => 'hidden'])
            ->assertJsonMissing(['slug' => 'other'])
            ->assertJsonMissingPath('data.0.governorate_id');
    }

    public function test_returns_404_for_an_inactive_governorate(): void
    {
        $governorate = Governorate::factory()->create([
            'slug' => 'inactive',
            'is_active' => false,
        ]);
        City::factory()->for($governorate)->create();

        $this->getJson(route('api.v1.governorates.cities.index', [
            'governorate' => $governorate->slug,
        ]))->assertNotFound();
    }

    public function test_returns_404_for_an_unknown_governorate(): void
    {
        $this->getJson(route('api.v1.governorates.cities.index', [
            'governorate' => 'missing',
        ]))->assertNotFound();
    }
}
