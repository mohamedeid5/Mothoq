<?php

namespace App\Models;

use App\Enums\BookingNotificationType;
use App\Enums\OutboxStatus;
use Database\Factories\OutboxMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['booking_id', 'type', 'recipient', 'payload', 'status', 'attempts', 'available_at', 'locked_until', 'lock_token', 'sent_at', 'last_error'])]
class OutboxMessage extends Model
{
    /** @use HasFactory<OutboxMessageFactory> */
    use HasFactory;

    public const int MAX_ATTEMPTS = 5;

    public const int LEASE_SECONDS = 180;

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    #[Scope]
    protected function ready(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->where(function (Builder $query): void {
                $query->where('status', OutboxStatus::Pending)->where('available_at', '<=', now());
            })->orWhere(function (Builder $query): void {
                $query->where('status', OutboxStatus::Processing)->where('locked_until', '<=', now());
            });
        });
    }

    protected function casts(): array
    {
        return [
            'type' => BookingNotificationType::class,
            'status' => OutboxStatus::class,
            'payload' => 'array',
            'attempts' => 'integer',
            'available_at' => 'immutable_datetime',
            'locked_until' => 'immutable_datetime',
            'sent_at' => 'immutable_datetime',
        ];
    }
}
