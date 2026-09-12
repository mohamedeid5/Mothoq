<?php

namespace Tests\Feature\Api\V1\Catalog;

use App\Models\Governorate;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class GovernorateIndexTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_only_active_governorates_ordered_by_name(): void
    {
        $zulu = Governorate::factory()->create(['name' => 'Zulu', 'slug' => 'zulu']);
        $alpha = Governorate::factory()->create(['name' => 'Alpha', 'slug' => 'alpha']);
        Governorate::factory()->create([
            'name' => 'Hidden',
            'slug' => 'hidden',
            'is_active' => false,
        ]);

        $response = $this->getJson(route('api.v1.governorates.index'));

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $alpha->id)
            ->assertJsonPath('data.0.slug', 'alpha')
            ->assertJsonPath('data.1.id', $zulu->id)
            ->assertJsonMissing(['slug' => 'hidden'])
            ->assertJsonMissingPath('data.0.is_active');
    }
}
