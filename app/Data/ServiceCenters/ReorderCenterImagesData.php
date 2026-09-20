<?php

namespace App\Data\ServiceCenters;

final readonly class ReorderCenterImagesData
{
    /** @param array{images: list<array{id: int|string, sort_order: int|string}>} $attributes */
    public static function fromArray(array $attributes): self
    {
        return new self(array_map(
            CenterImageOrderData::fromArray(...),
            $attributes['images'],
        ));
    }

    /** @param list<CenterImageOrderData> $images */
    public function __construct(public array $images) {}
}
