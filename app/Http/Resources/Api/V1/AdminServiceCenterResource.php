<?php

namespace App\Http\Resources\Api\V1;

use App\Models\CenterImage;
use App\Models\ServiceCenter;
use Illuminate\Http\Request;

/** @mixin ServiceCenter */
class AdminServiceCenterResource extends OwnerServiceCenterResource
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
            'services' => ServiceResource::collection($this->whenLoaded('services')),
            'car_brands' => CarBrandResource::collection($this->whenLoaded('carBrands')),
            'images' => $this->whenLoaded(
                'images',
                fn () => $this->images->map(fn (CenterImage $image): array => [
                    'id' => $image->id,
                    'path' => $image->path,
                    'alt_text' => $image->alt_text,
                    'is_cover' => $image->is_cover,
                    'sort_order' => $image->sort_order,
                ])->values(),
            ),
            'published_reviews_count' => $this->whenCounted('publishedReviews'),
            'published_reviews_average' => $this->whenAggregated('publishedReviews', 'rating', 'avg'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
