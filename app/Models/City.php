<?php

namespace App\Models;

use Database\Factories\CityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['governorate_id', 'name', 'slug', 'is_active'])]
class City extends Model
{
    /** @use HasFactory<CityFactory> */
    use HasFactory;

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    public function serviceCenters(): HasMany
    {
        return $this->hasMany(ServiceCenter::class);
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
