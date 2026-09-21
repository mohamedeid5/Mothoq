<?php

namespace App\Policies;

use App\Enums\ReviewStatus;
use App\Enums\ServiceCenterStatus;
use App\Enums\UserRole;
use App\Models\Review;
use App\Models\ServiceCenter;
use App\Models\User;

class ReviewPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->role === UserRole::Admin ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Review $review): bool
    {
        return $review->user_id === $user->id
            || $review->status === ReviewStatus::Published;
    }

    public function create(User $user, ServiceCenter $serviceCenter): bool
    {
        return $user->role === UserRole::Customer
            && $serviceCenter->status === ServiceCenterStatus::Published;
    }

    public function update(User $user, Review $review): bool
    {
        return $user->role === UserRole::Customer
            && $review->user_id === $user->id;
    }

    public function delete(User $user, Review $review): bool
    {
        return $this->update($user, $review);
    }

    public function moderate(User $user, Review $review): bool
    {
        return false;
    }
}
