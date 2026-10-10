<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'customer_id',
    'service_center_id',
    'service_id',
    'customer_phone',
    'scheduled_at',
    'duration_minutes',
    'notes',
    'status',
    'status_note',
    'responded_at',
    'cancelled_at',
    'completed_at',
])]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    public function scheduleLabel(): string
    {
        return $this->scheduled_at->format('Y-m-d H:i').' بتوقيت مصر';
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id')->withTrashed();
    }

    public function serviceCenter(): BelongsTo
    {
        return $this->belongsTo(ServiceCenter::class)->withTrashed();
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'scheduled_at' => 'immutable_datetime:Y-m-d H:i:s',
            'duration_minutes' => 'integer',
            'responded_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
