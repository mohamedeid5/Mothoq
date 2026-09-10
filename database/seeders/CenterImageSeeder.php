<?php

namespace Database\Seeders;

use App\Models\CenterImage;
use App\Models\ServiceCenter;
use Illuminate\Database\Seeder;

class CenterImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ServiceCenter::query()->each(function (ServiceCenter $serviceCenter): void {
            CenterImage::query()->updateOrCreate(
                ['service_center_id' => $serviceCenter->id, 'path' => "centers/{$serviceCenter->slug}/cover.jpg"],
                [
                    'alt_text' => "واجهة {$serviceCenter->name}",
                    'is_cover' => true,
                    'sort_order' => 0,
                ],
            );
        });
    }
}
