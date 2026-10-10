<?php

namespace App\Data\Bookings;

final readonly class CreateBookingData
{
    public function __construct(
        public int $serviceId,
        public string $customerPhone,
        public string $scheduledAt,
        public ?string $notes,
    ) {}
}
