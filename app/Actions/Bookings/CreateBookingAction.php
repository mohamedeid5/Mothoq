<?php

namespace App\Actions\Bookings;

use App\Actions\ServiceCenters\ResolveScheduleAction;
use App\Data\Bookings\CreateBookingData;
use App\Enums\BookingStatus;
use App\Mail\BookingCreatedConfirmation;
use App\Mail\NewBookingReceived;
use App\Models\Booking;
use App\Models\ServiceCenter;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

final class CreateBookingAction
{
    public function __construct(private readonly ResolveScheduleAction $schedules) {}

    public function handle(User $customer, ServiceCenter $serviceCenter, CreateBookingData $data): Booking
    {
        $booking = DB::transaction(function () use ($customer, $serviceCenter, $data): Booking {
            $lockedServiceCenter = ServiceCenter::query()
                ->whereKey($serviceCenter->id)
                ->lockForUpdate()
                ->firstOrFail();

            $service = $lockedServiceCenter->services()
                ->whereKey($data->serviceId)
                ->where('is_active', true)
                ->first();

            if ($service === null) {
                throw ValidationException::withMessages([
                    'service_id' => 'الخدمة المختارة غير متاحة في هذا المركز.',
                ]);
            }

            $scheduledAt = CarbonImmutable::parse($data->scheduledAt);
            $localInput = str_replace('T', ' ', $data->scheduledAt);
            $localInput = strlen($localInput) === 16 ? $localInput.':00' : $localInput;
            if ($scheduledAt->format('Y-m-d H:i:s') !== $localInput) {
                throw ValidationException::withMessages(['scheduled_at' => 'موعد الحجز غير صالح بتوقيت مصر.']);
            }
            if ($scheduledAt->lessThanOrEqualTo(CarbonImmutable::now())) {
                throw ValidationException::withMessages(['scheduled_at' => 'موعد الحجز يجب أن يكون في المستقبل.']);
            }

            $hasDuplicateBooking = Booking::query()
                ->whereBelongsTo($customer, 'customer')
                ->whereBelongsTo($lockedServiceCenter)
                ->where('scheduled_at', $scheduledAt->format('Y-m-d H:i:s'))
                ->whereIn('status', [BookingStatus::Pending, BookingStatus::Accepted])
                ->exists();

            if ($hasDuplicateBooking) {
                throw ValidationException::withMessages([
                    'scheduled_at' => 'لديك حجز قائم في نفس المركز وفي نفس الموعد.',
                ]);
            }

            $duration = $service->pivot->duration_minutes;
            if ($duration === null || $duration < 1) {
                throw ValidationException::withMessages(['service_id' => 'مدة هذه الخدمة غير محددة في المركز. لا يمكن حجزها حتى يتم تحديد المدة.']);
            }

            $this->schedules->validateBooking($lockedServiceCenter, $scheduledAt, (int) $duration);

            $booking = $customer->bookings()->create([
                'service_center_id' => $lockedServiceCenter->id,
                'service_id' => $service->id,
                'customer_phone' => $data->customerPhone,
                'scheduled_at' => $scheduledAt->format('Y-m-d H:i:s'),
                'duration_minutes' => (int) $duration,
                'notes' => $data->notes,
                'status' => BookingStatus::Pending,
            ]);

            return $booking->load($this->relations());
        }, 3);

        Mail::to($booking->customer)->send(new BookingCreatedConfirmation($booking));

        $owner = $booking->serviceCenter->owner;

        if ($owner !== null) {
            Mail::to($owner)->send(new NewBookingReceived($booking));
        }

        return $booking;
    }

    /** @return list<string> */
    private function relations(): array
    {
        return [
            'customer:id,name,email',
            'serviceCenter:id,name,slug,owner_id',
            'serviceCenter.owner:id,name,email',
            'service:id,name,slug',
        ];
    }
}
