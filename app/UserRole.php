<?php

namespace App;

enum UserRole: string
{
    case Customer = 'customer';
    case Admin = 'admin';
}
