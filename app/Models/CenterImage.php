<?php

namespace App\Models;

use Database\Factories\CenterImageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['service_center_id', 'disk', 'path', 'alt_text', 'is_cover', 'sort_order'])]
class CenterImage extends Model
{
    public const MAX_PER_SERVICE_CENTER = 10;

    /** @use HasFactory<CenterImageFactory> */
    use HasFactory;

    protected $attributes = ['disk' => 'public'];

    public function url(): string
    {
        $storage = Storage::disk($this->disk);

        return $this->disk === 's3'
            ? $storage->temporaryUrl($this->path, now()->addMinutes(15))
            : $storage->url($this->path);
    }

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
