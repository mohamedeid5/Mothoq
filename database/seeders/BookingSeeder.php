<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\ServiceCenter;
use App\Models\User;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customer = User::query()
            ->where('role', UserRole::Customer)
            ->first();

        if ($customer === null) {
            return;
        }

        ServiceCenter::query()
            ->with('services:id')
            ->get()
            ->each(function (ServiceCenter $serviceCenter, int $index) use ($customer): void {
                $service = $serviceCenter->services->first();

                if ($service === null) {
                    return;
                }

                Booking::query()->updateOrCreate(
                    [
                        'customer_id' => $customer->id,
                        'service_center_id' => $serviceCenter->id,
                    ],
                    [
                        'service_id' => $service->id,
                        'customer_phone' => '01000000010',
                        'scheduled_at' => now()->addDays($index + 2)->setTime(11, 0),
                        'notes' => 'فحص السيارة قبل الصيانة.',
                        'status' => BookingStatus::Pending,
                    ],
                );
            });
    }
}
