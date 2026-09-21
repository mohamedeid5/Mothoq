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
    private const DISK = 'public';

    public function handle(ServiceCenter $serviceCenter, StoreCenterImageData $data): CenterImage
    {
        $path = $data->image->storePublicly(
            "service-centers/{$serviceCenter->id}",
            self::DISK,
        );

        if ($path === false) {
            throw new RuntimeException('The service center image could not be stored.');
        }

        try {
            return DB::transaction(
                fn (): CenterImage => $this->createImage($serviceCenter, $data, $path),
            );
        } catch (Throwable $exception) {
            Storage::disk(self::DISK)->delete($path);

            throw $exception;
        }
    }

    private function createImage(ServiceCenter $serviceCenter, StoreCenterImageData $data, string $path): CenterImage
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
            'alt_text' => $data->altText,
            'is_cover' => $isCover,
            'sort_order' => $imagesCount === 0
                ? 0
                : ((int) $lockedServiceCenter->images()->max('sort_order')) + 1,
        ]);
    }
}
