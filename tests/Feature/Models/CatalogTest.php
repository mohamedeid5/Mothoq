<?php

namespace Tests\Feature\Models;

use App\Models\CarBrand;
use App\Models\CarModel;
use App\Models\City;
use App\Models\Governorate;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_city_names_may_repeat_in_different_governorates(): void
    {
        $firstGovernorate = Governorate::factory()->create();
        $secondGovernorate = Governorate::factory()->create();

        City::factory()->for($firstGovernorate)->create(['name' => 'المركز', 'slug' => 'center']);
        $secondCity = City::factory()->for($secondGovernorate)->create(['name' => 'المركز', 'slug' => 'center']);

        $this->assertModelExists($secondCity);
        $this->assertTrue($secondCity->governorate->is($secondGovernorate));
    }

    public function test_city_name_cannot_repeat_inside_the_same_governorate(): void
    {
        $governorate = Governorate::factory()->create();
        City::factory()->for($governorate)->create(['name' => 'المركز', 'slug' => 'center']);

        $this->expectException(QueryException::class);

        City::factory()->for($governorate)->create(['name' => 'المركز', 'slug' => 'another-center']);
    }

    public function test_car_models_belong_to_their_brand(): void
    {
        $brand = CarBrand::factory()->create();
        $models = CarModel::factory()->count(3)->for($brand)->create();

        $brand->load('carModels');

        $this->assertCount(3, $brand->carModels);
        $this->assertTrue($brand->carModels->contains($models->first()));
        $this->assertTrue($models->first()->carBrand->is($brand));
    }
}
