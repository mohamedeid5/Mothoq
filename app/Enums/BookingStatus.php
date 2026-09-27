<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'قيد المراجعة',
            self::Accepted => 'مقبول',
            self::Rejected => 'مرفوض',
            self::Cancelled => 'ملغي',
            self::Completed => 'مكتمل',
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return match ($this) {
            self::Pending => in_array($status,
                [
                    self::Accepted, self::Rejected, self::Cancelled,
                ],
                true
            ),
            self::Accepted => $status === self::Completed,
            self::Rejected, self::Cancelled, self::Completed => false,
        };
    }
}
