<?php

namespace App\Http\Resources\Api\V1;

use App\Models\CenterImage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin CenterImage */
class CenterImageResource extends JsonResource
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
            'path' => $this->path,
            'url' => Storage::disk('public')->url($this->path),
            'alt_text' => $this->alt_text,
            'is_cover' => $this->is_cover,
            'sort_order' => $this->sort_order,
        ];
    }
}
