<?php

namespace Database\Factories;

use App\Enums\ServiceCenterStatus;
use App\Models\City;
use App\Models\ServiceCenter;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ServiceCenter>
 */
class ServiceCenterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company().' Auto Service';

        return [
            'city_id' => City::factory(),
            'owner_id' => User::factory()->centerOwner(),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->paragraph(),
            'phone' => fake()->numerify('01#########'),
            'whatsapp' => fake()->numerify('01#########'),
            'address' => fake()->streetAddress(),
            'latitude' => fake()->latitude(22, 31.7),
            'longitude' => fake()->longitude(25, 35),
            'status' => ServiceCenterStatus::Draft,
            'verified_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ServiceCenterStatus::Published,
        ]);
    }

    public function verified(): static
    {
        return $this->published()->state(fn (array $attributes): array => [
            'verified_at' => now(),
        ]);
    }
}
