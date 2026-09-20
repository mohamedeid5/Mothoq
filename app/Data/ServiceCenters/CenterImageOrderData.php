<?php

namespace App\Data\ServiceCenters;

final readonly class CenterImageOrderData
{
    /** @param array{id: int|string, sort_order: int|string} $attributes */
    public static function fromArray(array $attributes): self
    {
        return new self(
            id: (int) $attributes['id'],
            sortOrder: (int) $attributes['sort_order'],
        );
    }

    public function __construct(
        public int $id,
        public int $sortOrder,
    ) {}
}
