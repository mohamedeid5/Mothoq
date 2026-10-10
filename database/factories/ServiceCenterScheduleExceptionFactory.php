<?php

namespace Database\Factories;

use App\Models\ServiceCenter;
use App\Models\ServiceCenterScheduleException;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ServiceCenterScheduleException> */
class ServiceCenterScheduleExceptionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'service_center_id' => ServiceCenter::factory(),
            'date' => fake()->dateTimeBetween('+1 day', '+1 year')->format('Y-m-d'),
            'opens_at' => '09:00',
            'closes_at' => '18:00',
            'is_closed' => false,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes): array => ['is_closed' => true, 'opens_at' => null, 'closes_at' => null]);
    }
}
