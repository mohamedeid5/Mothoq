<?php

namespace Database\Seeders;

use App\Models\CarBrand;
use Illuminate\Database\Seeder;

class CarBrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            ['name' => 'Toyota', 'slug' => 'toyota'],
            ['name' => 'Hyundai', 'slug' => 'hyundai'],
            ['name' => 'Kia', 'slug' => 'kia'],
            ['name' => 'Nissan', 'slug' => 'nissan'],
            ['name' => 'Chevrolet', 'slug' => 'chevrolet'],
            ['name' => 'Renault', 'slug' => 'renault'],
        ] as $brand) {
            CarBrand::query()->updateOrCreate(
                ['slug' => $brand['slug']],
                [...$brand, 'is_active' => true],
            );
        }
    }
}
