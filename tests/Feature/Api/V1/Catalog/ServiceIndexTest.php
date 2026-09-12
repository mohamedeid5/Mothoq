<?php

namespace Tests\Feature\Api\V1\Catalog;

use App\Models\Service;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ServiceIndexTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_only_active_services_ordered_by_name(): void
    {
        $zulu = Service::factory()->create(['name' => 'Zulu', 'slug' => 'zulu']);
        $alpha = Service::factory()->create([
            'name' => 'Alpha',
            'slug' => 'alpha',
            'description' => 'Alpha description',
            'icon' => 'icons/alpha.svg',
        ]);
        Service::factory()->create([
            'name' => 'Hidden',
            'slug' => 'hidden',
            'is_active' => false,
        ]);

        $response = $this->getJson(route('api.v1.services.index'));

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $alpha->id)
            ->assertJsonPath('data.0.description', 'Alpha description')
            ->assertJsonPath('data.0.icon', 'icons/alpha.svg')
            ->assertJsonPath('data.1.id', $zulu->id)
            ->assertJsonMissing(['slug' => 'hidden'])
            ->assertJsonMissingPath('data.0.is_active');
    }
}
