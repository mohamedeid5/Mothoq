<?php

namespace App\Enums;

enum BookingNotificationType: string
{
    case CustomerConfirmation = 'booking.created.customer';
    case OwnerNotification = 'booking.created.owner';
}
