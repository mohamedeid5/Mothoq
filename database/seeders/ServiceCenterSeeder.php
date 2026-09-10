<?php

namespace Database\Seeders;

use App\Enums\ServiceCenterStatus;
use App\Enums\UserRole;
use App\Models\CarBrand;
use App\Models\City;
use App\Models\Service;
use App\Models\ServiceCenter;
use App\Models\User;
use Illuminate\Database\Seeder;

class ServiceCenterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@mothoq.test'],
            [
                'name' => 'مدير موثوق',
                'password' => 'password',
                'role' => UserRole::Admin,
                'email_verified_at' => now(),
            ],
        );

        $centers = [
            [
                'name' => 'مركز النخبة لصيانة السيارات',
                'slug' => 'elite-auto-service',
                'city' => 'nasr-city',
                'address' => 'شارع مصطفى النحاس، مدينة نصر',
                'phone' => '01000000001',
                'brands' => ['toyota', 'hyundai', 'kia'],
                'services' => ['mechanics', 'electrical', 'oil-change', 'computer-diagnostics'],
                'verified' => true,
            ],
            [
                'name' => 'أوتو كير مصر الجديدة',
                'slug' => 'auto-care-heliopolis',
                'city' => 'heliopolis',
                'address' => 'شارع الحجاز، مصر الجديدة',
                'phone' => '01000000002',
                'brands' => ['nissan', 'renault', 'chevrolet'],
                'services' => ['mechanics', 'air-conditioning', 'suspension', 'tires'],
                'verified' => true,
            ],
            [
                'name' => 'المهندس لصيانة السيارات',
                'slug' => 'al-mohandes-auto-service',
                'city' => 'dokki',
                'address' => 'شارع التحرير، الدقي',
                'phone' => '01000000003',
                'brands' => ['toyota', 'nissan', 'kia'],
                'services' => ['mechanics', 'electrical', 'air-conditioning'],
                'verified' => false,
            ],
        ];

        foreach ($centers as $centerData) {
            $center = ServiceCenter::query()->updateOrCreate(
                ['slug' => $centerData['slug']],
                [
                    'city_id' => City::query()->where('slug', $centerData['city'])->valueOrFail('id'),
                    'created_by' => $admin->id,
                    'name' => $centerData['name'],
                    'description' => 'مركز متخصص يقدم خدمات صيانة وفحص السيارات.',
                    'phone' => $centerData['phone'],
                    'whatsapp' => $centerData['phone'],
                    'address' => $centerData['address'],
                    'status' => ServiceCenterStatus::Published,
                    'verified_at' => $centerData['verified'] ? now() : null,
                ],
            );

            $center->carBrands()->sync(
                CarBrand::query()->whereIn('slug', $centerData['brands'])->pluck('id'),
            );
            $center->services()->sync(
                Service::query()->whereIn('slug', $centerData['services'])->pluck('id'),
            );
        }
    }
}
