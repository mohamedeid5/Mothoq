<?php

namespace App\Actions\Bookings;

use App\Data\Bookings\UpdateBookingStatusData;
use App\Enums\BookingStatus;
use App\Mail\BookingStatusUpdated;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

final class UpdateBookingStatusAction
{
    public function handle(Booking $booking, UpdateBookingStatusData $data): Booking
    {
        $updatedBooking = DB::transaction(function () use ($booking, $data): Booking {
            $lockedBooking = Booking::query()
                ->whereKey($booking->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedBooking->status->canTransitionTo($data->status)) {
                throw ValidationException::withMessages([
                    'status' => sprintf(
                        'لا يمكن تحويل الحجز من "%s" إلى "%s".',
                        $lockedBooking->status->label(),
                        $data->status->label(),
                    ),
                ]);
            }

            $lockedBooking->update([
                'status' => $data->status,
                'status_note' => $data->statusNote,
                'responded_at' => in_array($data->status, [BookingStatus::Accepted, BookingStatus::Rejected], true)
                    ? now()
                    : $lockedBooking->responded_at,
                'completed_at' => $data->status === BookingStatus::Completed
                    ? now()
                    : null,
            ]);

            return $lockedBooking->refresh()->load([
                'customer:id,name,email',
                'serviceCenter:id,name,slug,timezone,owner_id',
                'service:id,name,slug',
            ]);
        });

        Mail::to($updatedBooking->customer)->send(new BookingStatusUpdated($updatedBooking));

        return $updatedBooking;
    }
}
