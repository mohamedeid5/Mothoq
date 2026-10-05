<?php

namespace App\Actions\ServiceCenters;

use App\Models\CenterImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class DeleteCenterImageAction
{
    public function handle(CenterImage $centerImage): void
    {
        DB::transaction(function () use ($centerImage): void {
            $serviceCenter = $centerImage->serviceCenter;
            $wasCover = $centerImage->is_cover;

            if (! Storage::disk($centerImage->disk)->delete($centerImage->path)) {
                throw new RuntimeException('The service center image could not be deleted.');
            }
            $centerImage->delete();

            if ($wasCover) {
                $serviceCenter->images()
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->first()
                    ?->update(['is_cover' => true]);
            }

        });
    }
}
