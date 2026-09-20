<?php

namespace App\Data\ServiceCenters;

final readonly class UpdateCenterImageData
{
    public function __construct(public ?string $altText) {}
}
