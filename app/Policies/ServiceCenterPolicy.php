<?php

namespace App\Policies;

use App\Enums\ServiceCenterStatus;
use App\Enums\UserRole;
use App\Models\ServiceCenter;
use App\Models\User;

class ServiceCenterPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->role === UserRole::Admin ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ServiceCenter $serviceCenter): bool
    {
        return $serviceCenter->status === ServiceCenterStatus::Published
            || $serviceCenter->owner_id === $user->id;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ServiceCenter $serviceCenter): bool
    {
        return $user->role === UserRole::CenterOwner
            && $serviceCenter->owner_id === $user->id;
    }

    public function publish(User $user, ServiceCenter $serviceCenter): bool
    {
        return false;
    }

    public function verify(User $user, ServiceCenter $serviceCenter): bool
    {
        return false;
    }

    public function transferOwnership(User $user, ServiceCenter $serviceCenter): bool
    {
        return false;
    }

    public function delete(User $user, ServiceCenter $serviceCenter): bool
    {
        return false;
    }

    public function restore(User $user, ServiceCenter $serviceCenter): bool
    {
        return false;
    }

    public function forceDelete(User $user, ServiceCenter $serviceCenter): bool
    {
        return false;
    }
}
