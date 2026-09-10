<?php

namespace App\Queries\ServiceCenters;

final readonly class PublicServiceCenterFilters
{
    public function __construct(
        public ?string $governorate = null,
        public ?string $city = null,
        public ?string $service = null,
        public ?string $carBrand = null,
        public ?bool $verified = null,
        public int $page = 1,
        public int $perPage = 15,
    ) {}
}
