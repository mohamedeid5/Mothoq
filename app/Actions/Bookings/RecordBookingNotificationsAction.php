<?php

namespace App\Actions\Bookings;

use App\Data\Bookings\BookingMailData;
use App\Enums\BookingNotificationType;
use App\Models\Booking;
use App\Models\OutboxMessage;
use Illuminate\Support\Facades\DB;
use LogicException;

final class RecordBookingNotificationsAction
{
    /** Must share the transaction that creates the booking. */
    public function handle(Booking $booking): void
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Booking notifications must be recorded inside the booking transaction.');
        }

        $payload = BookingMailData::fromBooking($booking)->toArray();
        $this->record($booking, BookingNotificationType::CustomerConfirmation, $booking->customer->email, $payload);
        $owner = $booking->serviceCenter->owner;

        if ($owner !== null) {
            $this->record($booking, BookingNotificationType::OwnerNotification, $owner->email, $payload);
        }
    }

    /** @param array<string, int|string|null> $payload */
    private function record(Booking $booking, BookingNotificationType $type, string $recipient, array $payload): void
    {
        OutboxMessage::query()->create([
            'booking_id' => $booking->id,
            'type' => $type,
            'recipient' => $recipient,
            'payload' => $payload,
            'available_at' => now(),
        ]);
    }
}
