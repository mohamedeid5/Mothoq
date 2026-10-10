<?php

namespace App\Http\Controllers\Api\V1\Owner;

use App\Actions\ServiceCenters\UpdateServiceDurationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceCenters\UpdateServiceDurationRequest;
use App\Http\Resources\Api\V1\ServiceResource;
use App\Queries\ServiceCenters\OwnerServiceCenterQuery;
use Illuminate\Support\Facades\Gate;

class ServiceDurationController extends Controller
{
    public function update(UpdateServiceDurationRequest $request, int $serviceCenter, int $service, OwnerServiceCenterQuery $centers, UpdateServiceDurationAction $update): ServiceResource
    {
        $center = $centers->findForOwnerOrFail($request->user(), $serviceCenter);
        Gate::authorize('update', $center);
        $duration = $request->validated('duration_minutes');

        return new ServiceResource($update->handle($center, $service, $duration === null ? null : (int) $duration));
    }
}
