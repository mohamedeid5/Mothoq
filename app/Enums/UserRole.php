<?php

namespace App\Enums;

enum UserRole: string
{
    case Customer = 'customer';
    case CenterOwner = 'center_owner';
    case Admin = 'admin';
}
