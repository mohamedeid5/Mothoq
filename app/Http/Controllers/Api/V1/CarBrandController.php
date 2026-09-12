<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CarBrandResource;
use App\Queries\Catalog\PublicCatalogQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class CarBrandController extends Controller
{
    public function index(PublicCatalogQuery $catalog): AnonymousResourceCollection
    {
        return CarBrandResource::collection($catalog->carBrands());
    }
}
