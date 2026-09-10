<?php

namespace Database\Seeders;

use App\Models\Governorate;
use Illuminate\Database\Seeder;

class GovernorateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            ['name' => 'القاهرة', 'slug' => 'cairo'],
            ['name' => 'الجيزة', 'slug' => 'giza'],
            ['name' => 'الإسكندرية', 'slug' => 'alexandria'],
        ] as $governorate) {
            Governorate::query()->updateOrCreate(
                ['slug' => $governorate['slug']],
                [...$governorate, 'is_active' => true],
            );
        }
    }
}
