<?php

namespace App\Data\Bookings;

use Carbon\CarbonImmutable;

final readonly class CreateBookingData
{
    public function __construct(
        public int $serviceId,
        public string $customerPhone,
        public CarbonImmutable $scheduledAt,
        public ?string $notes,
    ) {}
}
