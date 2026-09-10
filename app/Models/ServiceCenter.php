<?php

namespace App\Models;

use App\Enums\ServiceCenterStatus;
use Database\Factories\ServiceCenterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'city_id',
    'owner_id',
    'name',
    'slug',
    'description',
    'phone',
    'whatsapp',
    'address',
    'latitude',
    'longitude',
    'status',
    'verified_at',
])]
class ServiceCenter extends Model
{
    /** @use HasFactory<ServiceCenterFactory> */
    use HasFactory, SoftDeletes;

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class);
    }

    public function carBrands(): BelongsToMany
    {
        return $this->belongsToMany(CarBrand::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function publishedReviews(): HasMany
    {
        return $this->reviews()->published();
    }

    public function images(): HasMany
    {
        return $this->hasMany(CenterImage::class);
    }

    public function openingHours(): HasMany
    {
        return $this->hasMany(OpeningHour::class);
    }

    #[Scope]
    protected function published(Builder $query): Builder
    {
        return $query->where('status', ServiceCenterStatus::Published);
    }

    #[Scope]
    protected function verified(Builder $query): Builder
    {
        return $query->whereNotNull('verified_at');
    }

    protected function casts(): array
    {
        return [
            'status' => ServiceCenterStatus::class,
            'verified_at' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }
}
