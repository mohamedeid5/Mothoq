<?php

namespace App\Actions\ServiceCenters;

use App\Models\CenterImage;
use Illuminate\Support\Facades\DB;

final class SetCenterCoverImageAction
{
    public function handle(CenterImage $centerImage): CenterImage
    {
        return DB::transaction(function () use ($centerImage): CenterImage {
            $centerImage->serviceCenter
                ->images()
                ->whereKeyNot($centerImage->id)
                ->where('is_cover', true)
                ->update(['is_cover' => false]);

            $centerImage->update(['is_cover' => true]);

            return $centerImage->refresh();
        });
    }
}
