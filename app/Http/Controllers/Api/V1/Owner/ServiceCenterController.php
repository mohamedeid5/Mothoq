<?php

namespace App\Http\Controllers\Api\V1\Owner;

use App\Actions\ServiceCenters\UpdateServiceCenterAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceCenters\UpdateServiceCenterRequest;
use App\Http\Resources\Api\V1\OwnerServiceCenterResource;
use App\Queries\ServiceCenters\OwnerServiceCenterQuery;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class ServiceCenterController extends Controller
{
    public function __construct(private OwnerServiceCenterQuery $serviceCenters) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $serviceCenters = $this->serviceCenters->paginateFor($request->user());

        return OwnerServiceCenterResource::collection($serviceCenters);
    }

    public function show(Request $request, int $serviceCenter): OwnerServiceCenterResource
    {
        $serviceCenterModel = $this->serviceCenters->findForOwnerOrFail(
            $request->user(),
            $serviceCenter,
        );
        Gate::authorize('view', $serviceCenterModel);

        return new OwnerServiceCenterResource($serviceCenterModel);
    }

    public function update(
        UpdateServiceCenterRequest $request,
        int $serviceCenter,
        UpdateServiceCenterAction $updateServiceCenter,
    ): OwnerServiceCenterResource {
        $serviceCenterModel = $this->serviceCenters->findForOwnerOrFail(
            $request->user(),
            $serviceCenter,
        );
        Gate::authorize('update', $serviceCenterModel);

        $updateServiceCenter->handle($serviceCenterModel, $request->toData());
        $serviceCenterModel->load(['owner', 'city.governorate']);

        return new OwnerServiceCenterResource($serviceCenterModel);
    }
}
