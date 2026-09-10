<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            ['name' => 'ميكانيكا', 'slug' => 'mechanics', 'icon' => 'wrench'],
            ['name' => 'كهرباء', 'slug' => 'electrical', 'icon' => 'zap'],
            ['name' => 'تكييف', 'slug' => 'air-conditioning', 'icon' => 'snowflake'],
            ['name' => 'عفشة', 'slug' => 'suspension', 'icon' => 'settings'],
            ['name' => 'سمكرة ودهان', 'slug' => 'body-and-paint', 'icon' => 'paintbrush'],
            ['name' => 'إطارات', 'slug' => 'tires', 'icon' => 'circle'],
            ['name' => 'تغيير زيوت', 'slug' => 'oil-change', 'icon' => 'droplet'],
            ['name' => 'فحص كمبيوتر', 'slug' => 'computer-diagnostics', 'icon' => 'scan-line'],
        ] as $service) {
            Service::query()->updateOrCreate(
                ['slug' => $service['slug']],
                [...$service, 'is_active' => true],
            );
        }
    }
}
