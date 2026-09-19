<?php

namespace App\Http\Resources\Api\V1;

use App\Models\OpeningHour;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OpeningHour */
class OpeningHourResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'day' => $this->day_of_week->value,
            'day_label' => $this->day_of_week->label(),
            'opens_at' => $this->opens_at === null ? null : substr($this->opens_at, 0, 5),
            'closes_at' => $this->closes_at === null ? null : substr($this->closes_at, 0, 5),
            'is_closed' => $this->is_closed,
        ];
    }
}
