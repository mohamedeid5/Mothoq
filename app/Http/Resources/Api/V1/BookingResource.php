<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Booking */
class BookingResource extends JsonResource
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
            'customer_phone' => $this->customer_phone,
            'scheduled_at' => $this->scheduled_at->format('Y-m-d H:i:s'),
            'duration_minutes' => $this->duration_minutes,
            'schedule_requires_review' => $this->duration_minutes === null,
            'ends_at' => $this->duration_minutes !== null
                ? $this->scheduled_at->addMinutes($this->duration_minutes)->format('Y-m-d H:i:s') : null,
            'notes' => $this->notes,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_note' => $this->status_note,
            'responded_at' => $this->responded_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'customer' => [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'email' => $this->customer->email,
            ],
            'service_center' => [
                'id' => $this->serviceCenter->id,
                'name' => $this->serviceCenter->name,
                'slug' => $this->serviceCenter->slug,
            ],
            'service' => [
                'id' => $this->service->id,
                'name' => $this->service->name,
                'slug' => $this->service->slug,
            ],
        ];
    }
}
