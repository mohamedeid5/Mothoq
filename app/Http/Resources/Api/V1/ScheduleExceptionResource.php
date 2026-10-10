<?php

namespace App\Http\Resources\Api\V1;

use App\Models\ServiceCenterScheduleException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ServiceCenterScheduleException */
class ScheduleExceptionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date->toDateString(),
            'is_closed' => $this->is_closed,
            'opens_at' => $this->opens_at === null ? null : substr($this->opens_at, 0, 5),
            'closes_at' => $this->closes_at === null ? null : substr($this->closes_at, 0, 5),
        ];
    }
}
