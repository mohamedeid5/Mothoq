<?php

namespace App\Actions\Reviews;

use App\Data\Reviews\UpdateReviewData;
use App\Enums\ReviewStatus;
use App\Models\Review;

final class UpdateReviewAction
{
    public function handle(Review $review, UpdateReviewData $data): Review
    {
        $review->update([
            'rating' => $data->rating,
            'comment' => $data->comment,
            'status' => ReviewStatus::Pending,
            'published_at' => null,
        ]);

        return $review->refresh()->load(['user:id,name', 'serviceCenter:id,name,slug']);
    }
}
