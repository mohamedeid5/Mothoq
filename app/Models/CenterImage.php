<?php

namespace App\Models;

use Database\Factories\CenterImageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['service_center_id', 'path', 'alt_text', 'is_cover', 'sort_order'])]
class CenterImage extends Model
{
    /** @use HasFactory<CenterImageFactory> */
    use HasFactory;

    public function serviceCenter(): BelongsTo
    {
        return $this->belongsTo(ServiceCenter::class);
    }

    protected function casts(): array
    {
        return [
            'is_cover' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
