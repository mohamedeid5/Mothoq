<?php

namespace App\Actions\ServiceCenters;

use App\Enums\BookingStatus;
use App\Models\ServiceCenter;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

final class ValidateScheduleBookingsAction
{
    public function __construct(private readonly ResolveScheduleAction $schedules) {}

    /** The caller holds the center lock and has applied the proposed change within its transaction. */
    public function handle(ServiceCenter $center, string $errorKey, ?string $date = null): void
    {
        foreach ($center->bookings()->whereIn('status', [BookingStatus::Pending, BookingStatus::Accepted])->lazyById() as $booking) {
            if ($booking->scheduled_at_timezone !== 'UTC') {
                throw ValidationException::withMessages([
                    $errorKey => 'يوجد حجز نشط قديم بتوقيت غير مؤكد. يجب مراجعته قبل تعديل الجدول.',
                ]);
            }

            $start = CarbonImmutable::parse($booking->getRawOriginal('scheduled_at'), 'UTC');
            if ($date !== null && $start->setTimezone($center->timezone)->toDateString() !== $date) {
                continue;
            }

            if ($booking->duration_minutes === null) {
                throw ValidationException::withMessages([
                    $errorKey => 'يوجد حجز نشط قديم بمدة غير محددة. يجب مراجعته قبل تعديل الجدول.',
                ]);
            }

            if (! $this->schedules->allows($center, $start, $booking->duration_minutes)) {
                throw ValidationException::withMessages([
                    $errorKey => 'التعديل يتعارض مع حجز نشط قائم. لم يتم تغيير الجدول أو الحجز.',
                ]);
            }
        }
    }
}
