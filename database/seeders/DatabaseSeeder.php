<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            GovernorateSeeder::class,
            CitySeeder::class,
            ServiceSeeder::class,
            CarBrandSeeder::class,
            CarModelSeeder::class,
            ServiceCenterSeeder::class,
            OpeningHourSeeder::class,
            CenterImageSeeder::class,
            ReviewSeeder::class,
        ]);
    }
}
