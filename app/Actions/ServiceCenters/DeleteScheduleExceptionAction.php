<?php

namespace App\Actions\ServiceCenters;

use App\Models\ServiceCenter;
use Illuminate\Support\Facades\DB;

final class DeleteScheduleExceptionAction
{
    public function __construct(private readonly ValidateScheduleBookingsAction $bookings) {}

    public function handle(ServiceCenter $center, int $exceptionId): void
    {
        DB::transaction(function () use ($center, $exceptionId): void {
            $center = ServiceCenter::query()->whereKey($center->id)->lockForUpdate()->firstOrFail();
            $exception = $center->scheduleExceptions()->findOrFail($exceptionId);
            $date = $exception->date->toDateString();
            $exception->delete();
            $this->bookings->handle($center, 'date', $date);
        }, 3);
    }
}
