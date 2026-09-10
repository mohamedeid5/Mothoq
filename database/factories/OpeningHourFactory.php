<?php

namespace Database\Factories;

use App\Enums\DayOfWeek;
use App\Models\OpeningHour;
use App\Models\ServiceCenter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OpeningHour>
 */
class OpeningHourFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_center_id' => ServiceCenter::factory(),
            'day_of_week' => fake()->randomElement(DayOfWeek::cases()),
            'opens_at' => '09:00',
            'closes_at' => '18:00',
            'is_closed' => false,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'opens_at' => null,
            'closes_at' => null,
            'is_closed' => true,
        ]);
    }
}
