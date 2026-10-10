<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\ServiceCenters\UpdateServiceDurationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceCenters\UpdateServiceDurationRequest;
use App\Http\Resources\Api\V1\ServiceResource;
use App\Queries\ServiceCenters\AdminServiceCenterQuery;
use Illuminate\Support\Facades\Gate;

class ServiceDurationController extends Controller
{
    public function update(UpdateServiceDurationRequest $request, int $serviceCenter, int $service, AdminServiceCenterQuery $centers, UpdateServiceDurationAction $update): ServiceResource
    {
        $center = $centers->findOrFail($serviceCenter);
        Gate::authorize('update', $center);
        $duration = $request->validated('duration_minutes');

        return new ServiceResource($update->handle($center, $service, $duration === null ? null : (int) $duration));
    }
}
