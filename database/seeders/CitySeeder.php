<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Governorate;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $citiesByGovernorate = [
            'cairo' => [
                ['name' => 'مدينة نصر', 'slug' => 'nasr-city'],
                ['name' => 'مصر الجديدة', 'slug' => 'heliopolis'],
                ['name' => 'القاهرة الجديدة', 'slug' => 'new-cairo'],
            ],
            'giza' => [
                ['name' => 'الدقي', 'slug' => 'dokki'],
                ['name' => 'السادس من أكتوبر', 'slug' => '6th-of-october'],
            ],
            'alexandria' => [
                ['name' => 'سموحة', 'slug' => 'smouha'],
                ['name' => 'سيدي جابر', 'slug' => 'sidi-gaber'],
            ],
        ];

        foreach ($citiesByGovernorate as $governorateSlug => $cities) {
            $governorate = Governorate::query()->where('slug', $governorateSlug)->firstOrFail();

            foreach ($cities as $city) {
                City::query()->updateOrCreate(
                    ['governorate_id' => $governorate->id, 'slug' => $city['slug']],
                    [...$city, 'is_active' => true],
                );
            }
        }
    }
}
