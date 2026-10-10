<?php

namespace App\Actions\ServiceCenters;

use App\Models\Service;
use App\Models\ServiceCenter;
use Illuminate\Support\Facades\DB;

final class UpdateServiceDurationAction
{
    public function handle(ServiceCenter $center, int $serviceId, ?int $duration): Service
    {
        return DB::transaction(function () use ($center, $serviceId, $duration): Service {
            $center = ServiceCenter::query()->whereKey($center->id)->lockForUpdate()->firstOrFail();
            $center->services()->whereKey($serviceId)->firstOrFail();
            $center->services()->updateExistingPivot($serviceId, ['duration_minutes' => $duration]);

            return $center->services()->whereKey($serviceId)->firstOrFail();
        }, 3);
    }
}
