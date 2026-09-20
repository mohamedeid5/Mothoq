<?php

namespace App\Actions\ServiceCenters;

use App\Models\CenterImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class DeleteCenterImageAction
{
    public function handle(CenterImage $centerImage): void
    {
        $path = DB::transaction(function () use ($centerImage): string {
            $serviceCenter = $centerImage->serviceCenter;
            $wasCover = $centerImage->is_cover;
            $path = $centerImage->path;

            $centerImage->delete();

            if ($wasCover) {
                $serviceCenter->images()
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->first()
                    ?->update(['is_cover' => true]);
            }

            return $path;
        });

        Storage::disk('public')->delete($path);
    }
}
