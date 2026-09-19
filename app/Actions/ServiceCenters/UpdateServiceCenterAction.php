<?php

namespace App\Actions\ServiceCenters;

use App\Data\ServiceCenters\UpdateServiceCenterData;
use App\Models\ServiceCenter;

class UpdateServiceCenterAction
{
    public function handle(ServiceCenter $serviceCenter, UpdateServiceCenterData $data): void
    {
        $serviceCenter->update($data->toArray());
    }
}
