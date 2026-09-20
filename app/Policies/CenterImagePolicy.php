<?php

namespace App\Policies;

use App\Enums\ServiceCenterStatus;
use App\Enums\UserRole;
use App\Models\CenterImage;
use App\Models\ServiceCenter;
use App\Models\User;

class CenterImagePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->role === UserRole::Admin ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CenterImage $centerImage): bool
    {
        return $centerImage->serviceCenter->status === ServiceCenterStatus::Published
            || $centerImage->serviceCenter->owner_id === $user->id;
    }

    public function create(User $user, ServiceCenter $serviceCenter): bool
    {
        return $user->role === UserRole::CenterOwner
            && $serviceCenter->owner_id === $user->id;
    }

    public function reorder(User $user, ServiceCenter $serviceCenter): bool
    {
        return $this->create($user, $serviceCenter);
    }

    public function update(User $user, CenterImage $centerImage): bool
    {
        return $user->role === UserRole::CenterOwner
            && $centerImage->serviceCenter->owner_id === $user->id;
    }

    public function delete(User $user, CenterImage $centerImage): bool
    {
        return $this->update($user, $centerImage);
    }
}
