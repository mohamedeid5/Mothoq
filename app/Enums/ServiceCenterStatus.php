<?php

namespace App\Enums;

enum ServiceCenterStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'مسودة',
            self::Published => 'منشور',
            self::Suspended => 'موقوف',
        };
    }
}
