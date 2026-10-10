<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ServiceCenterScheduleExceptionFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['date', 'opens_at', 'closes_at', 'is_closed'])]
class ServiceCenterScheduleException extends Model
{
    /** @use HasFactory<ServiceCenterScheduleExceptionFactory> */
    use HasFactory;

    public function serviceCenter(): BelongsTo
    {
        return $this->belongsTo(ServiceCenter::class);
    }

    /** Keep calendar dates canonical on SQLite as well as MySQL DATE columns. */
    public function setDateAttribute(DateTimeInterface|string $value): void
    {
        $this->attributes['date'] = CarbonImmutable::parse($value)->toDateString();
    }

    protected function casts(): array
    {
        return ['date' => 'immutable_date', 'is_closed' => 'boolean'];
    }
}
