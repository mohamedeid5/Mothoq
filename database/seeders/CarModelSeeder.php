<?php

namespace Database\Seeders;

use App\Models\CarBrand;
use App\Models\CarModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CarModelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modelsByBrand = [
            'toyota' => ['Corolla', 'Yaris', 'Fortuner'],
            'hyundai' => ['Elantra', 'Accent', 'Tucson'],
            'kia' => ['Cerato', 'Sportage', 'Picanto'],
            'nissan' => ['Sunny', 'Qashqai', 'Patrol'],
            'chevrolet' => ['Optra', 'Aveo', 'Captiva'],
            'renault' => ['Logan', 'Megane', 'Duster'],
        ];

        foreach ($modelsByBrand as $brandSlug => $modelNames) {
            $brand = CarBrand::query()->where('slug', $brandSlug)->firstOrFail();

            foreach ($modelNames as $modelName) {
                CarModel::query()->updateOrCreate(
                    ['car_brand_id' => $brand->id, 'slug' => Str::slug($modelName)],
                    ['name' => $modelName, 'is_active' => true],
                );
            }
        }
    }
}
