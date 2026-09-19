<?php

namespace App\Actions\ServiceCenters;

use App\Models\ServiceCenter;

class SetServiceCenterVerificationAction
{
    public function handle(ServiceCenter $serviceCenter, bool $verified): void
    {
        $serviceCenter->update([
            'verified_at' => $verified ? now() : null,
        ]);
    }
}
