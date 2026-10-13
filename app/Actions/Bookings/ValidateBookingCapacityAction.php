<?php

namespace App\Actions\Bookings;

use App\Enums\BookingStatus;
use App\Models\ServiceCenter;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

final class ValidateBookingCapacityAction
{
    public function configuredCapacity(ServiceCenter $center): int
    {
        if ($center->booking_capacity === null || $center->booking_capacity < 1) {
            throw ValidationException::withMessages([
                'booking_capacity' => 'سعة الحجوزات غير محددة لهذا المركز. يجب على إدارة المركز تحديدها قبل استقبال حجوزات جديدة.',
            ]);
        }

        return $center->booking_capacity;
    }

    /** The caller must hold the center row lock until the booking or capacity change commits. */
    public function handle(ServiceCenter $center, int $capacity, ?CarbonImmutable $start = null, ?int $durationMinutes = null, ?int $excludeBookingId = null): void
    {
        $events = [];
        $end = null;
        if ($start !== null) {
            if ($durationMinutes === null || $durationMinutes < 1) {
                $this->unknownDuration();
            }
            $end = $start->addMinutes($durationMinutes);
            $events = [[$start->format('Y-m-d H:i:s'), 1], [$end->format('Y-m-d H:i:s'), -1]];
        }

        $query = $center->bookings()
            ->whereIn('status', [BookingStatus::Pending, BookingStatus::Accepted])
            ->when($excludeBookingId !== null, fn (Builder $query): Builder => $query->whereKeyNot($excludeBookingId));

        if ($start !== null) {
            $query->where('scheduled_at', '<', $end->format('Y-m-d H:i:s'))
                ->where(function (Builder $query) use ($start): void {
                    $query->whereNull('end_at')->orWhereNull('duration_minutes')->orWhere('duration_minutes', '<', 1)
                        ->orWhere('end_at', '>', $start->format('Y-m-d H:i:s'));
                });
        }

        foreach ($query->select(['id', 'scheduled_at', 'end_at', 'duration_minutes'])->lockForUpdate()->get() as $booking) {
            if ($booking->duration_minutes === null || $booking->duration_minutes < 1 || $booking->end_at === null) {
                $this->unknownDuration();
            }
            $events[] = [$booking->scheduled_at->format('Y-m-d H:i:s'), 1];
            $events[] = [$booking->end_at->format('Y-m-d H:i:s'), -1];
        }

        usort($events, fn (array $left, array $right): int => ($left[0] <=> $right[0]) ?: ($left[1] <=> $right[1]));
        $occupancy = 0;
        foreach ($events as [, $change]) {
            $occupancy += $change;
            if ($occupancy > $capacity) {
                throw ValidationException::withMessages([
                    'booking_capacity' => 'سعة المركز لا تسمح بالحجوزات المتزامنة في هذا الموعد. اختر موعدًا آخر أو راجع السعة المحددة.',
                ]);
            }
        }
    }

    private function unknownDuration(): never
    {
        throw ValidationException::withMessages([
            'booking_capacity' => 'يوجد حجز نشط بمدة غير معروفة أو غير صالحة. يلزم مراجعته إداريًا قبل حساب السعة.',
        ]);
    }
}
