<?php

namespace App\Actions\ServiceCenters;

use App\Data\ServiceCenters\OpeningHourData;
use App\Data\ServiceCenters\UpdateOpeningHoursData;
use App\Models\OpeningHour;
use App\Models\ServiceCenter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class UpdateOpeningHoursAction
{
    /** @return Collection<int, OpeningHour> */
    public function handle(ServiceCenter $serviceCenter, UpdateOpeningHoursData $data): Collection
    {
        return DB::transaction(function () use ($serviceCenter, $data): Collection {
            foreach ($data->openingHours as $openingHour) {
                $this->updateDay($serviceCenter, $openingHour);
            }

            return $serviceCenter->openingHours()
                ->get()
                ->sortBy(fn (OpeningHour $openingHour): int => $openingHour->day_of_week->sortOrder())
                ->values();
        });
    }

    private function updateDay(ServiceCenter $serviceCenter, OpeningHourData $openingHour): void
    {
        $serviceCenter->openingHours()->updateOrCreate(
            ['day_of_week' => $openingHour->day],
            [
                'opens_at' => $openingHour->opensAt,
                'closes_at' => $openingHour->closesAt,
                'is_closed' => $openingHour->isClosed,
            ],
        );
    }
}
