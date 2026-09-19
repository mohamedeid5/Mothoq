<?php

namespace App\Enums;

enum DayOfWeek: string
{
    case Saturday = 'saturday';
    case Sunday = 'sunday';
    case Monday = 'monday';
    case Tuesday = 'tuesday';
    case Wednesday = 'wednesday';
    case Thursday = 'thursday';
    case Friday = 'friday';

    public function label(): string
    {
        return match ($this) {
            self::Saturday => 'السبت',
            self::Sunday => 'الأحد',
            self::Monday => 'الاثنين',
            self::Tuesday => 'الثلاثاء',
            self::Wednesday => 'الأربعاء',
            self::Thursday => 'الخميس',
            self::Friday => 'الجمعة',
        };
    }

    public function sortOrder(): int
    {
        return match ($this) {
            self::Saturday => 0,
            self::Sunday => 1,
            self::Monday => 2,
            self::Tuesday => 3,
            self::Wednesday => 4,
            self::Thursday => 5,
            self::Friday => 6,
        };
    }
}
