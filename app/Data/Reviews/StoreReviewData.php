<?php

namespace App\Data\Reviews;

final readonly class StoreReviewData
{
    public function __construct(
        public int $rating,
        public ?string $comment,
    ) {}
}
