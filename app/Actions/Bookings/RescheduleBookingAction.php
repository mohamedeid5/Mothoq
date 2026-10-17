<?php

namespace App\Actions\Bookings;

use App\Actions\ServiceCenters\ResolveScheduleAction;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\ServiceCenter;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RescheduleBookingAction
{
    public function __construct(private readonly ResolveScheduleAction $schedules, private readonly ValidateBookingCapacityAction $capacity) {}

    public function handle(Booking $booking, string $requestedAt): Booking
    {
        return DB::transaction(function () use ($booking, $requestedAt): Booking {
            $center = ServiceCenter::withTrashed()->whereKey($booking->service_center_id)->lockForUpdate()->firstOrFail();
            $lockedBooking = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if ($lockedBooking->status !== BookingStatus::Pending) {
                throw ValidationException::withMessages(['booking' => 'لا يمكن تغيير موعد الحجز بعد معالجته من المركز.']);
            }

            $duration = $lockedBooking->duration_minutes;
            if ($duration === null || $duration < 1 || $lockedBooking->end_at === null) {
                throw ValidationException::withMessages(['booking' => 'مدة الحجز غير معروفة أو غير صالحة. يلزم مراجعتها قبل تغيير الموعد.']);
            }

            $scheduledAt = CarbonImmutable::parse($requestedAt);
            $localInput = str_replace('T', ' ', $requestedAt);
            $localInput = strlen($localInput) === 16 ? $localInput.':00' : $localInput;
            if ($scheduledAt->format('Y-m-d H:i:s') !== $localInput) {
                throw ValidationException::withMessages(['scheduled_at' => 'موعد الحجز غير صالح بتوقيت مصر.']);
            }
            if ($scheduledAt->lessThanOrEqualTo(CarbonImmutable::now())) {
                throw ValidationException::withMessages(['scheduled_at' => 'موعد الحجز يجب أن يكون في المستقبل.']);
            }

            $hasDuplicateBooking = Booking::query()
                ->where('customer_id', $lockedBooking->customer_id)
                ->where('service_center_id', $center->id)
                ->whereKeyNot($lockedBooking->id)
                ->where('scheduled_at', $scheduledAt->format('Y-m-d H:i:s'))
                ->whereIn('status', [BookingStatus::Pending, BookingStatus::Accepted])
                ->exists();
            if ($hasDuplicateBooking) {
                throw ValidationException::withMessages(['scheduled_at' => 'لديك حجز قائم في نفس المركز وفي نفس الموعد.']);
            }

            $this->schedules->validateBooking($center, $scheduledAt, $duration);
            $this->capacity->handle($center, $this->capacity->configuredCapacity($center), $scheduledAt, $duration, $lockedBooking->id);

            $lockedBooking->update(['scheduled_at' => $scheduledAt->format('Y-m-d H:i:s')]);

            return $lockedBooking->refresh()->load([
                'customer:id,name,email',
                'serviceCenter:id,name,slug,owner_id',
                'service:id,name,slug',
            ]);
        }, 3);
    }
}
