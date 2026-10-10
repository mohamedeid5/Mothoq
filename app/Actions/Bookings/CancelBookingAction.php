<?php

namespace App\Actions\Bookings;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CancelBookingAction
{
    public function handle(Booking $booking): Booking
    {
        return DB::transaction(function () use ($booking): Booking {
            $lockedBooking = Booking::query()
                ->whereKey($booking->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedBooking->status->canTransitionTo(BookingStatus::Cancelled)) {
                throw ValidationException::withMessages([
                    'booking' => 'لا يمكن إلغاء الحجز بعد معالجته من المركز.',
                ]);
            }

            $lockedBooking->update([
                'status' => BookingStatus::Cancelled,
                'cancelled_at' => now(),
            ]);

            return $lockedBooking->refresh()->load([
                'customer:id,name,email',
                'serviceCenter:id,name,slug,timezone,owner_id',
                'service:id,name,slug',
            ]);
        });
    }
}
