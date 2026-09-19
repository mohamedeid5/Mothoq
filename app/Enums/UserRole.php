<?php

namespace App\Enums;

enum UserRole: string
{
    case Customer = 'customer';
    case CenterOwner = 'center_owner';
    case Admin = 'admin';

    public function dashboardRouteName(): string
    {
        return match ($this) {
            self::Admin => 'admin.dashboard',
            self::CenterOwner => 'owner.dashboard',
            self::Customer => 'home',
        };
    }
}
