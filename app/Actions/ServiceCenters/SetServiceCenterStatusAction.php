<?php

namespace App\Actions\ServiceCenters;

use App\Enums\ServiceCenterStatus;
use App\Models\ServiceCenter;

class SetServiceCenterStatusAction
{
    public function handle(ServiceCenter $serviceCenter, ServiceCenterStatus $status): void
    {
        $serviceCenter->update(['status' => $status]);
    }
}
