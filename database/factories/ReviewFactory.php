<?php

namespace Database\Factories;

use App\Models\Review;
use App\Models\ServiceCenter;
use App\Models\User;
use App\ReviewStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'service_center_id' => ServiceCenter::factory(),
            'rating' => fake()->numberBetween(1, 5),
            'comment' => fake()->paragraph(),
            'status' => ReviewStatus::Pending,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ReviewStatus::Published,
            'published_at' => now(),
        ]);
    }
}
