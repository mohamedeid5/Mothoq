<?php

namespace Database\Factories;

use App\Models\CenterImage;
use App\Models\ServiceCenter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CenterImage>
 */
class CenterImageFactory extends Factory
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
            'path' => 'centers/'.fake()->uuid().'.jpg',
            'alt_text' => fake()->sentence(4),
            'is_cover' => false,
            'sort_order' => 0,
        ];
    }

    public function cover(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_cover' => true,
            'sort_order' => 0,
        ]);
    }
}
