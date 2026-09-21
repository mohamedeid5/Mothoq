<?php

namespace App\Actions\Reviews;

use App\Enums\ReviewStatus;
use App\Models\Review;
use InvalidArgumentException;

final class SetReviewStatusAction
{
    public function handle(Review $review, ReviewStatus $status): Review
    {
        if ($status === ReviewStatus::Pending) {
            throw new InvalidArgumentException('A moderated review must be published or rejected.');
        }

        $review->update([
            'status' => $status,
            'published_at' => $status === ReviewStatus::Published ? now() : null,
        ]);

        return $review->refresh()->load(['user:id,name', 'serviceCenter:id,name,slug']);
    }
}
