<?php

namespace App\Actions\ServiceCenters;

use App\Data\ServiceCenters\UpdateServiceCenterData;
use App\Models\ServiceCenter;
use Illuminate\Support\Facades\DB;

class UpdateServiceCenterAction
{
    public function handle(ServiceCenter $serviceCenter, UpdateServiceCenterData $data): void
    {
        DB::transaction(function () use ($serviceCenter, $data): void {
            ServiceCenter::query()->whereKey($serviceCenter->id)->lockForUpdate()->firstOrFail();
            $serviceCenter->refresh();
            $serviceCenter->update($data->toArray());

            if ($data->serviceIds !== null) {
                $serviceCenter->services()->sync($data->serviceIds);
            }

            if ($data->carBrandIds !== null) {
                $serviceCenter->carBrands()->sync($data->carBrandIds);
            }
        }, 3);
    }
}
