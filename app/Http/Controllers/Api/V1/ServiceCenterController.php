<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexServiceCenterRequest;
use App\Http\Resources\Api\V1\ServiceCenterResource;
use App\Http\Resources\Api\V1\ServiceCenterSummaryResource;
use App\Queries\ServiceCenters\PublicServiceCenterQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServiceCenterController extends Controller
{
    public function __construct(private readonly PublicServiceCenterQuery $serviceCenters) {}

    public function index(IndexServiceCenterRequest $request): AnonymousResourceCollection
    {
        $serviceCenters = $this->serviceCenters
            ->paginate($request->toFilters())
            ->withQueryString();

        return ServiceCenterSummaryResource::collection($serviceCenters);
    }

    public function show(string $serviceCenter): ServiceCenterResource
    {
        return new ServiceCenterResource(
            $this->serviceCenters->findBySlugOrFail($serviceCenter),
        );
    }
}
