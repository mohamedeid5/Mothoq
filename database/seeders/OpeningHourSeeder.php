<?php

namespace Database\Seeders;

use App\Enums\DayOfWeek;
use App\Models\OpeningHour;
use App\Models\ServiceCenter;
use Illuminate\Database\Seeder;

class OpeningHourSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ServiceCenter::query()->each(function (ServiceCenter $serviceCenter): void {
            foreach (DayOfWeek::cases() as $day) {
                $isClosed = $day === DayOfWeek::Friday;

                OpeningHour::query()->updateOrCreate(
                    ['service_center_id' => $serviceCenter->id, 'day_of_week' => $day],
                    [
                        'opens_at' => $isClosed ? null : '09:00',
                        'closes_at' => $isClosed ? null : '18:00',
                        'is_closed' => $isClosed,
                    ],
                );
            }
        });
    }
}
