<?php

namespace App\Actions\ServiceCenters;

use App\Models\ServiceCenter;
use App\Models\ServiceCenterScheduleException;
use Illuminate\Support\Facades\DB;

final class UpdateScheduleExceptionAction
{
    public function __construct(private readonly ValidateScheduleBookingsAction $bookings) {}

    /** @param array{date: string, is_closed: bool|int|string, opens_at?: ?string, closes_at?: ?string} $data */
    public function handle(ServiceCenter $center, array $data): ServiceCenterScheduleException
    {
        return DB::transaction(function () use ($center, $data): ServiceCenterScheduleException {
            $center = ServiceCenter::query()->whereKey($center->id)->lockForUpdate()->firstOrFail();
            $exception = $center->scheduleExceptions()->updateOrCreate(
                ['date' => $data['date']],
                ['is_closed' => $data['is_closed'], 'opens_at' => $data['opens_at'] ?? null, 'closes_at' => $data['closes_at'] ?? null],
            );
            $this->bookings->handle($center, 'date', $data['date']);

            return $exception;
        }, 3);
    }
}
