<?php

namespace App\Data\Bookings;

use App\Models\Booking;

final readonly class BookingMailData
{
    public function __construct(
        public int $bookingId,
        public string $customerName,
        public ?string $ownerName,
        public string $centerName,
        public string $serviceName,
        public string $scheduleLabel,
        public string $statusLabel,
        public string $customerPhone,
        public ?string $notes,
    ) {}

    public static function fromBooking(Booking $booking): self
    {
        $booking->loadMissing(['customer', 'serviceCenter.owner', 'service']);

        return new self(
            $booking->id,
            $booking->customer->name,
            $booking->serviceCenter->owner?->name,
            $booking->serviceCenter->name,
            $booking->service->name,
            $booking->scheduleLabel(),
            $booking->status->label(),
            $booking->customer_phone,
            $booking->notes,
        );
    }

    /** @return array{bookingId: int, customerName: string, ownerName: ?string, centerName: string, serviceName: string, scheduleLabel: string, statusLabel: string, customerPhone: string, notes: ?string} */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /** @param array{bookingId: int, customerName: string, ownerName: ?string, centerName: string, serviceName: string, scheduleLabel: string, statusLabel: string, customerPhone: string, notes: ?string} $data */
    public static function fromArray(array $data): self
    {
        return new self(...$data);
    }
}
