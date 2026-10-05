<?php

namespace App\Actions\ServiceCenters;

use App\Data\ServiceCenters\StoreCenterImageData;
use App\Models\CenterImage;
use App\Models\ServiceCenter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

final class StoreCenterImageAction
{
    public function handle(ServiceCenter $serviceCenter, StoreCenterImageData $data): CenterImage
    {
        $disk = config('filesystems.center_images_disk');
        $path = $data->image->store(
            "service-centers/{$serviceCenter->id}",
            ['disk' => $disk, 'visibility' => $disk === 's3' ? 'private' : 'public'],
        );

        if ($path === false) {
            throw new RuntimeException('The service center image could not be stored.');
        }

        try {
            return DB::transaction(
                fn (): CenterImage => $this->createImage($serviceCenter, $data, $path, $disk),
            );
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($path);

            throw $exception;
        }
    }

    private function createImage(ServiceCenter $serviceCenter, StoreCenterImageData $data, string $path, string $disk): CenterImage
    {
        $lockedServiceCenter = ServiceCenter::query()
            ->whereKey($serviceCenter->id)
            ->lockForUpdate()
            ->firstOrFail();

        $imagesCount = $lockedServiceCenter->images()->count();

        if ($imagesCount >= CenterImage::MAX_PER_SERVICE_CENTER) {
            throw ValidationException::withMessages([
                'image' => 'لا يمكن إضافة أكثر من 10 صور للمركز.',
            ]);
        }

        $isCover = $imagesCount === 0 || $data->isCover;

        if ($isCover) {
            $lockedServiceCenter->images()->update(['is_cover' => false]);
        }

        return $lockedServiceCenter->images()->create([
            'path' => $path,
            'disk' => $disk,
            'alt_text' => $data->altText,
            'is_cover' => $isCover,
            'sort_order' => $imagesCount === 0
                ? 0
                : ((int) $lockedServiceCenter->images()->max('sort_order')) + 1,
        ]);
    }
}
