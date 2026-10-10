<?php

namespace Database\Seeders;

use App\Enums\DayOfWeek;
use App\Enums\ServiceCenterStatus;
use App\Models\CarBrand;
use App\Models\City;
use App\Models\Governorate;
use App\Models\Service;
use App\Models\ServiceCenter;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PublishedDemoSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        DB::transaction(function (): void {
            $services = [];

            foreach ([
                'mechanics' => ['ميكانيكا', 'wrench'],
                'electrical' => ['كهرباء', 'zap'],
                'air-conditioning' => ['تكييف', 'snowflake'],
                'suspension' => ['عفشة', 'settings'],
                'body-and-paint' => ['سمكرة ودهان', 'paintbrush'],
                'tires' => ['إطارات', 'circle'],
                'oil-change' => ['تغيير زيوت', 'droplet'],
                'computer-diagnostics' => ['فحص كمبيوتر', 'scan-line'],
            ] as $slug => [$name, $icon]) {
                $service = Service::query()->firstOrCreate(['slug' => $slug], [
                    'name' => $name, 'icon' => $icon, 'is_active' => true,
                ]);

                if ($service->is_active) {
                    $services[$slug] = $service->id;
                }
            }

            $brands = [];

            foreach (['toyota' => 'Toyota', 'hyundai' => 'Hyundai', 'kia' => 'Kia', 'nissan' => 'Nissan', 'chevrolet' => 'Chevrolet', 'renault' => 'Renault'] as $slug => $name) {
                $brand = CarBrand::query()->firstOrCreate(['slug' => $slug], [
                    'name' => $name, 'is_active' => true,
                ]);

                if ($brand->is_active) {
                    $brands[$slug] = $brand->id;
                }
            }

            foreach ($this->locations() as $location) {
                $governorate = Governorate::query()->firstOrCreate(['slug' => $location['governorate_slug']], [
                    'name' => $location['governorate'], 'is_active' => true,
                ]);
                $city = City::query()->firstOrCreate([
                    'governorate_id' => $governorate->id, 'slug' => $location['slug'],
                ], ['name' => $location['name'], 'is_active' => true]);

                if (! $governorate->is_active || ! $city->is_active) {
                    continue;
                }

                foreach ($this->specialties() as $specialty) {
                    $slug = 'demo-'.$location['slug'].'-'.$specialty['slug'];

                    if (ServiceCenter::withTrashed()->where('slug', $slug)->exists()) {
                        continue;
                    }

                    $center = ServiceCenter::query()->create([
                        'slug' => $slug,
                        'name' => $specialty['name'].' — '.$location['name'].' (تجريبي)',
                        'city_id' => $city->id,
                        'description' => 'بيانات تجريبية لعرض تجربة موثوق، وليست لمركز حقيقي. '.$specialty['description'].' لا تتوجه إلى هذا العنوان ولا تعتمد على هذه البيانات لطلب خدمة فعلية.',
                        'phone' => 'غير متاح (تجريبي)',
                        'address' => 'عنوان افتراضي للتجربة — '.$location['name'].'، '.$location['governorate'],
                        'status' => ServiceCenterStatus::Published,
                    ]);

                    $center->services()->attach(array_values(array_intersect_key($services, array_flip($specialty['services']))));
                    $center->carBrands()->attach(array_values(array_intersect_key($brands, array_flip($specialty['brands']))));

                    foreach (DayOfWeek::cases() as $day) {
                        $isClosed = $day === DayOfWeek::Friday;
                        $center->openingHours()->create([
                            'day_of_week' => $day,
                            'opens_at' => $isClosed ? null : '09:00',
                            'closes_at' => $isClosed ? null : '18:00',
                            'is_closed' => $isClosed,
                        ]);
                    }
                }
            }
        });
    }

    /** @return list<array{governorate_slug: string, governorate: string, slug: string, name: string}> */
    private function locations(): array
    {
        return [
            ['governorate_slug' => 'cairo', 'governorate' => 'القاهرة', 'slug' => 'nasr-city', 'name' => 'مدينة نصر'],
            ['governorate_slug' => 'cairo', 'governorate' => 'القاهرة', 'slug' => 'heliopolis', 'name' => 'مصر الجديدة'],
            ['governorate_slug' => 'cairo', 'governorate' => 'القاهرة', 'slug' => 'new-cairo', 'name' => 'القاهرة الجديدة'],
            ['governorate_slug' => 'giza', 'governorate' => 'الجيزة', 'slug' => 'dokki', 'name' => 'الدقي'],
            ['governorate_slug' => 'giza', 'governorate' => 'الجيزة', 'slug' => '6th-of-october', 'name' => 'السادس من أكتوبر'],
            ['governorate_slug' => 'alexandria', 'governorate' => 'الإسكندرية', 'slug' => 'smouha', 'name' => 'سموحة'],
            ['governorate_slug' => 'alexandria', 'governorate' => 'الإسكندرية', 'slug' => 'sidi-gaber', 'name' => 'سيدي جابر'],
        ];
    }

    /** @return list<array{slug: string, name: string, description: string, services: list<string>, brands: list<string>}> */
    private function specialties(): array
    {
        return [
            [
                'slug' => 'maintenance',
                'name' => 'مركز الصيانة الشاملة',
                'description' => 'نموذج لخدمات الصيانة الدورية وتغيير الزيوت وفحص الكمبيوتر وكهرباء وتكييف السيارات.',
                'services' => ['mechanics', 'oil-change', 'computer-diagnostics', 'electrical', 'air-conditioning'],
                'brands' => ['toyota', 'hyundai', 'kia'],
            ],
            [
                'slug' => 'care',
                'name' => 'مركز العناية بالسيارات',
                'description' => 'نموذج لخدمات صيانة العفشة والإطارات والسمكرة والدهان والفحص الميكانيكي.',
                'services' => ['suspension', 'tires', 'body-and-paint', 'mechanics'],
                'brands' => ['nissan', 'chevrolet', 'renault'],
            ],
        ];
    }
}
