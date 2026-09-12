<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ServiceResource;
use App\Queries\Catalog\PublicCatalogQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ServiceController extends Controller
{
    public function index(PublicCatalogQuery $catalog): AnonymousResourceCollection
    {
        return ServiceResource::collection($catalog->services());
    }
}
