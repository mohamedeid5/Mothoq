<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Review */
class ReviewResource extends JsonResource
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
            'rating' => $this->rating,
            'comment' => $this->comment,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],
            'service_center' => $this->whenLoaded('serviceCenter', fn (): array => [
                'id' => $this->serviceCenter->id,
                'name' => $this->serviceCenter->name,
                'slug' => $this->serviceCenter->slug,
            ]),
        ];
    }
}
