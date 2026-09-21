<?php

namespace App\Data\Reviews;

final readonly class UpdateReviewData
{
    public function __construct(
        public int $rating,
        public ?string $comment,
    ) {}
}
