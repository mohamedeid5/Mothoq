<?php

namespace App\Actions\Bookings;

use App\Data\Bookings\CreateBookingData;
use App\Enums\BookingStatus;
use App\Mail\NewBookingReceived;
use App\Models\Booking;
use App\Models\ServiceCenter;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

final class CreateBookingAction
{
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

            $hasDuplicateBooking = Booking::query()
                ->whereBelongsTo($customer, 'customer')
                ->whereBelongsTo($lockedServiceCenter)
                ->where('scheduled_at', $data->scheduledAt)
                ->whereIn('status', [BookingStatus::Pending, BookingStatus::Accepted])
                ->exists();

            if ($hasDuplicateBooking) {
                throw ValidationException::withMessages([
                    'scheduled_at' => 'لديك حجز قائم في نفس المركز وفي نفس الموعد.',
                ]);
            }

            $booking = $customer->bookings()->create([
                'service_center_id' => $lockedServiceCenter->id,
                'service_id' => $service->id,
                'customer_phone' => $data->customerPhone,
                'scheduled_at' => $data->scheduledAt,
                'notes' => $data->notes,
                'status' => BookingStatus::Pending,
            ]);

            return $booking->load($this->relations());
        });

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
