<?php

namespace App\Actions\ServiceCenters;

use App\Data\ServiceCenters\CenterImageOrderData;
use App\Data\ServiceCenters\ReorderCenterImagesData;
use App\Models\CenterImage;
use App\Models\ServiceCenter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ReorderCenterImagesAction
{
    /** @return Collection<int, CenterImage> */
    public function handle(ServiceCenter $serviceCenter, ReorderCenterImagesData $data): Collection
    {
        return DB::transaction(function () use ($serviceCenter, $data): Collection {
            $savedImageIds = $serviceCenter->images()
                ->lockForUpdate()
                ->pluck('id')
                ->sort()
                ->values()
                ->all();
            $submittedImageIds = collect($data->images)
                ->pluck('id')
                ->sort()
                ->values()
                ->all();

            if ($savedImageIds !== $submittedImageIds) {
                throw ValidationException::withMessages([
                    'images' => 'يجب إرسال ترتيب جميع صور المركز فقط.',
                ]);
            }

            foreach ($data->images as $image) {
                $this->updateSortOrder($serviceCenter, $image);
            }

            return $serviceCenter->images()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
        });
    }

    private function updateSortOrder(ServiceCenter $serviceCenter, CenterImageOrderData $image): void
    {
        $serviceCenter->images()
            ->whereKey($image->id)
            ->update(['sort_order' => $image->sortOrder]);
    }
}
