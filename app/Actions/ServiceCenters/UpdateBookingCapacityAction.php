<?php

namespace App\Actions\ServiceCenters;

use App\Actions\Bookings\ValidateBookingCapacityAction;
use App\Models\ServiceCenter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final class UpdateBookingCapacityAction
{
    public function __construct(private readonly ValidateBookingCapacityAction $capacity) {}

    public function handle(ServiceCenter $center, int $bookingCapacity): ServiceCenter
    {
        Validator::make(['booking_capacity' => $bookingCapacity], [
            'booking_capacity' => ['required', 'integer', 'between:1,2147483647'],
        ])->validate();

        return DB::transaction(function () use ($center, $bookingCapacity): ServiceCenter {
            $center = ServiceCenter::query()->whereKey($center->id)->lockForUpdate()->firstOrFail();
            $this->capacity->handle($center, $bookingCapacity);
            $center->booking_capacity = $bookingCapacity;
            $center->save();

            return $center;
        }, 3);
    }
}
