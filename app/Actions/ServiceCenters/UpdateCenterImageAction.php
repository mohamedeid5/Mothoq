<?php

namespace App\Actions\ServiceCenters;

use App\Data\ServiceCenters\UpdateCenterImageData;
use App\Models\CenterImage;

final class UpdateCenterImageAction
{
    public function handle(CenterImage $centerImage, UpdateCenterImageData $data): CenterImage
    {
        $centerImage->update(['alt_text' => $data->altText]);

        return $centerImage->refresh();
    }
}
