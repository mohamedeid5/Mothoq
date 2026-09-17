<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\ServiceCenters\CreateServiceCenterAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreServiceCenterRequest;
use App\Http\Resources\Api\V1\OwnerServiceCenterResource;
use Illuminate\Http\JsonResponse;

class ServiceCenterController extends Controller
{
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
}
