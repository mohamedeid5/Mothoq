<?php

namespace Database\Seeders;

use App\Enums\ReviewStatus;
use App\Models\Review;
use App\Models\ServiceCenter;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = collect([
            ['name' => 'أحمد محمد', 'email' => 'ahmed@example.com'],
            ['name' => 'سارة علي', 'email' => 'sara@example.com'],
            ['name' => 'محمود حسن', 'email' => 'mahmoud@example.com'],
        ])->map(fn (array $customer): User => User::query()->firstOrCreate(
            ['email' => $customer['email']],
            [...$customer, 'password' => 'password', 'email_verified_at' => now()],
        ));

        ServiceCenter::query()->get()->each(function (ServiceCenter $serviceCenter, int $index) use ($customers): void {
            $customer = $customers[$index % $customers->count()];

            Review::query()->updateOrCreate(
                ['user_id' => $customer->id, 'service_center_id' => $serviceCenter->id],
                [
                    'rating' => 5 - ($index % 2),
                    'comment' => 'الخدمة كانت جيدة والتعامل محترم والمعلومات مطابقة للواقع.',
                    'status' => ReviewStatus::Published,
                    'published_at' => now(),
                ],
            );
        });
    }
}
