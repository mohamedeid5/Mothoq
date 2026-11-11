<?php

namespace Database\Factories;

use App\Enums\BookingNotificationType;
use App\Enums\OutboxStatus;
use App\Models\Booking;
use App\Models\OutboxMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OutboxMessage> */
class OutboxMessageFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'type' => BookingNotificationType::CustomerConfirmation,
            'recipient' => 'customer@example.com',
            'payload' => fn (array $attributes): array => [
                'bookingId' => $attributes['booking_id'],
                'customerName' => 'أحمد',
                'ownerName' => 'محمد',
                'centerName' => 'مركز الاختبار',
                'serviceName' => 'تغيير زيت',
                'scheduleLabel' => '2026-11-20 10:00 بتوقيت مصر',
                'statusLabel' => 'قيد المراجعة',
                'customerPhone' => '01012345678',
                'notes' => null,
            ],
            'status' => OutboxStatus::Pending,
            'available_at' => now(),
        ];
    }

    public function sent(): static
    {
        return $this->state(fn (): array => ['status' => OutboxStatus::Sent, 'sent_at' => now()]);
    }

    public function failed(): static
    {
        return $this->state(fn (): array => ['status' => OutboxStatus::Failed, 'attempts' => OutboxMessage::MAX_ATTEMPTS]);
    }
}
