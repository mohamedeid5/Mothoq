<?php

namespace App\Policies;

use App\Enums\BookingStatus;
use App\Enums\ServiceCenterStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\ServiceCenter;
use App\Models\User;

class BookingPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->role === UserRole::Admin ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::Customer, UserRole::CenterOwner], true);
    }

    public function view(User $user, Booking $booking): bool
    {
        return $booking->customer_id === $user->id
            || $this->ownsBookingCenter($user, $booking);
    }

    public function create(User $user, ServiceCenter $serviceCenter): bool
    {
        return $user->role === UserRole::Customer
            && $serviceCenter->status === ServiceCenterStatus::Published;
    }

    public function cancel(User $user, Booking $booking): bool
    {
        return $user->role === UserRole::Customer
            && $booking->customer_id === $user->id
            && $booking->status === BookingStatus::Pending;
    }

    public function updateStatus(User $user, Booking $booking): bool
    {
        return $user->role === UserRole::CenterOwner
            && $this->ownsBookingCenter($user, $booking);
    }

    private function ownsBookingCenter(User $user, Booking $booking): bool
    {
        return $user->role === UserRole::CenterOwner
            && $user->serviceCenters()->whereKey($booking->service_center_id)->exists();
    }
}
