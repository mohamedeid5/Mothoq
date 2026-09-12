<?php

namespace App\Models;

use Database\Factories\CarBrandFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'logo_path', 'is_active'])]
class CarBrand extends Model
{
    /** @use HasFactory<CarBrandFactory> */
    use HasFactory;

    public function carModels(): HasMany
    {
        return $this->hasMany(CarModel::class);
    }

    public function serviceCenters(): BelongsToMany
    {
        return $this->belongsToMany(ServiceCenter::class);
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
