<?php

namespace App\Http\Resources\Api\V1;

use App\Models\OpeningHour;
use App\Models\ServiceCenter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ServiceCenter */
class OwnerServiceCenterResource extends JsonResource
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
            'owner' => [
                'id' => $this->owner->id,
                'name' => $this->owner->name,
                'email' => $this->owner->email,
            ],
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
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'phone' => $this->phone,
            'whatsapp' => $this->whatsapp,
            'address' => $this->address,
            'coordinates' => $this->latitude !== null && $this->longitude !== null
                ? [
                    'latitude' => (float) $this->latitude,
                    'longitude' => (float) $this->longitude,
                ]
                : null,
            'status' => $this->status->value,
            'is_verified' => $this->verified_at !== null,
            'services' => ServiceResource::collection($this->whenLoaded('services')),
            'car_brands' => CarBrandResource::collection($this->whenLoaded('carBrands')),
            'opening_hours' => $this->whenLoaded(
                'openingHours',
                fn () => OpeningHourResource::collection(
                    $this->openingHours
                        ->sortBy(fn (OpeningHour $openingHour): int => $openingHour->day_of_week->sortOrder())
                        ->values(),
                ),
            ),
        ];
    }
}
