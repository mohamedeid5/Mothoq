<?php

namespace App\Http\Resources\Api\V1;

use App\Models\CenterImage;
use App\Models\OpeningHour;
use App\Models\ServiceCenter;
use Illuminate\Http\Request;

/** @mixin ServiceCenter */
class ServiceCenterResource extends ServiceCenterSummaryResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'whatsapp' => $this->whatsapp,
            'images' => $this->images->map(fn (CenterImage $image): array => [
                'id' => $image->id,
                'path' => $image->path,
                'alt_text' => $image->alt_text,
                'is_cover' => $image->is_cover,
                'sort_order' => $image->sort_order,
            ])->values(),
            'opening_hours' => OpeningHourResource::collection(
                $this->openingHours
                    ->sortBy(fn (OpeningHour $openingHour): int => $openingHour->day_of_week->sortOrder())
                    ->values(),
            ),
            'latest_reviews' => ReviewResource::collection($this->publishedReviews),
        ];
    }
}
