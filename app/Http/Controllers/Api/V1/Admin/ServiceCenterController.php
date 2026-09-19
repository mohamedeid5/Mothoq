<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\ServiceCenters\CreateServiceCenterAction;
use App\Actions\ServiceCenters\SetServiceCenterStatusAction;
use App\Actions\ServiceCenters\SetServiceCenterVerificationAction;
use App\Actions\ServiceCenters\UpdateServiceCenterAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexServiceCenterRequest;
use App\Http\Requests\Admin\UpdateServiceCenterStatusRequest;
use App\Http\Requests\Admin\UpdateServiceCenterVerificationRequest;
use App\Http\Requests\Api\V1\Admin\StoreServiceCenterRequest;
use App\Http\Requests\ServiceCenters\UpdateServiceCenterRequest;
use App\Http\Resources\Api\V1\AdminServiceCenterResource;
use App\Http\Resources\Api\V1\OwnerServiceCenterResource;
use App\Models\ServiceCenter;
use App\Queries\ServiceCenters\AdminServiceCenterQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class ServiceCenterController extends Controller
{
    public function __construct(private readonly AdminServiceCenterQuery $serviceCenters) {}

    public function index(IndexServiceCenterRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', ServiceCenter::class);

        return AdminServiceCenterResource::collection(
            $this->serviceCenters->paginate(
                search: $request->search(),
                status: $request->status(),
                verified: $request->verified(),
            ),
        );
    }

    public function show(int $serviceCenter): AdminServiceCenterResource
    {
        $serviceCenterModel = $this->serviceCenters->findOrFail($serviceCenter);
        Gate::authorize('view', $serviceCenterModel);

        return new AdminServiceCenterResource($serviceCenterModel);
    }

    public function store(
        StoreServiceCenterRequest $request,
        CreateServiceCenterAction $createServiceCenter,
    ): JsonResponse {
        $serviceCenter = $createServiceCenter->handle($request->toData());

        $serviceCenter->load(['owner', 'city.governorate']);

        return (new OwnerServiceCenterResource($serviceCenter))
            ->response()
            ->setStatusCode(201);
    }

    public function updateStatus(
        UpdateServiceCenterStatusRequest $request,
        int $serviceCenter,
        SetServiceCenterStatusAction $setStatus,
    ): AdminServiceCenterResource {
        $serviceCenterModel = $this->serviceCenters->findOrFail($serviceCenter);
        Gate::authorize('publish', $serviceCenterModel);

        $setStatus->handle($serviceCenterModel, $request->status());

        return new AdminServiceCenterResource(
            $this->serviceCenters->findOrFail($serviceCenter),
        );
    }

    public function update(
        UpdateServiceCenterRequest $request,
        int $serviceCenter,
        UpdateServiceCenterAction $updateServiceCenter,
    ): AdminServiceCenterResource {
        $serviceCenterModel = $this->serviceCenters->findOrFail($serviceCenter);
        Gate::authorize('update', $serviceCenterModel);

        $updateServiceCenter->handle($serviceCenterModel, $request->toData());

        return new AdminServiceCenterResource(
            $this->serviceCenters->findOrFail($serviceCenter),
        );
    }

    public function updateVerification(
        UpdateServiceCenterVerificationRequest $request,
        int $serviceCenter,
        SetServiceCenterVerificationAction $setVerification,
    ): AdminServiceCenterResource {
        $serviceCenterModel = $this->serviceCenters->findOrFail($serviceCenter);
        Gate::authorize('verify', $serviceCenterModel);

        $setVerification->handle($serviceCenterModel, $request->boolean('verified'));

        return new AdminServiceCenterResource(
            $this->serviceCenters->findOrFail($serviceCenter),
        );
    }
}
