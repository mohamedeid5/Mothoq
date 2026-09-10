<?php

namespace App\Models;

use Database\Factories\CarModelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['car_brand_id', 'name', 'slug', 'is_active'])]
class CarModel extends Model
{
    /** @use HasFactory<CarModelFactory> */
    use HasFactory;

    public function carBrand(): BelongsTo
    {
        return $this->belongsTo(CarBrand::class);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
