<?php

namespace App\Http\Resources\Api\V1;

use App\Models\CarBrand;
use App\Models\Service;
use App\Models\ServiceCenter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ServiceCenter */
class ServiceCenterSummaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'phone' => $this->phone,
            'address' => $this->address,
            'coordinates' => $this->latitude !== null && $this->longitude !== null
                ? [
                    'latitude' => (float) $this->latitude,
                    'longitude' => (float) $this->longitude,
                ]
                : null,
            'is_verified' => $this->verified_at !== null,
            'city' => [
                'id' => $this->city->id,
                'name' => $this->city->name,
                'slug' => $this->city->slug,
                'governorate' => [
                    'id' => $this->city->governorate->id,
                    'name' => $this->city->governorate->name,
                    'slug' => $this->city->governorate->slug,
                ],
            ],
            'services' => $this->services->map(fn (Service $service): array => [
                'id' => $service->id,
                'name' => $service->name,
                'slug' => $service->slug,
                'icon' => $service->icon,
            ])->values(),
            'car_brands' => $this->carBrands->map(fn (CarBrand $carBrand): array => [
                'id' => $carBrand->id,
                'name' => $carBrand->name,
                'slug' => $carBrand->slug,
                'logo_path' => $carBrand->logo_path,
            ])->values(),
            'cover_image' => $this->coverImage === null ? null : [
                'path' => $this->coverImage->path,
                'alt_text' => $this->coverImage->alt_text,
            ],
            'rating' => [
                'average' => $this->published_reviews_avg_rating === null
                    ? null
                    : round((float) $this->published_reviews_avg_rating, 1),
                'count' => (int) $this->published_reviews_count,
            ],
        ];
    }
}
