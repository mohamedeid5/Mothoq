<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CityResource;
use App\Queries\Catalog\PublicCatalogQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class GovernorateCityController extends Controller
{
    public function index(string $governorate, PublicCatalogQuery $catalog): AnonymousResourceCollection
    {
        return CityResource::collection($catalog->citiesForGovernorate($governorate));
    }
}
