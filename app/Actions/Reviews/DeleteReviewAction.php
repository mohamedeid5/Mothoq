<?php

namespace App\Actions\Reviews;

use App\Models\Review;

final class DeleteReviewAction
{
    public function handle(Review $review): void
    {
        $review->delete();
    }
}
