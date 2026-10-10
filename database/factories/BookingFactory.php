<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Service;
use App\Models\ServiceCenter;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => User::factory(),
            'service_center_id' => ServiceCenter::factory()->published(),
            'service_id' => Service::factory(),
            'customer_phone' => fake()->numerify('01#########'),
            'scheduled_at' => now()->addDays(fake()->numberBetween(1, 14))->setTime(10, 0),
            'duration_minutes' => 30,
            'scheduled_at_timezone' => 'UTC',
            'notes' => fake()->optional()->sentence(),
            'status' => BookingStatus::Pending,
            'status_note' => null,
            'responded_at' => null,
            'cancelled_at' => null,
            'completed_at' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Booking $booking): void {
            ServiceCenter::query()
                ->findOrFail($booking->service_center_id)
                ->services()
                ->syncWithoutDetaching([$booking->service_id]);
        });
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => BookingStatus::Accepted,
            'responded_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => BookingStatus::Rejected,
            'responded_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => BookingStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->accepted()->state(fn (array $attributes): array => [
            'status' => BookingStatus::Completed,
            'completed_at' => now(),
        ]);
    }
}
