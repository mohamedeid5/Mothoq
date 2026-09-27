<?php

namespace App\Data\Bookings;

use App\Enums\BookingStatus;

final readonly class UpdateBookingStatusData
{
    public function __construct(
        public BookingStatus $status,
        public ?string $statusNote,
    ) {}
}
