<?php

namespace App\Data\ServiceCenters;

use Illuminate\Http\UploadedFile;

final readonly class StoreCenterImageData
{
    public function __construct(
        public UploadedFile $image,
        public ?string $altText,
        public bool $isCover,
    ) {}
}
