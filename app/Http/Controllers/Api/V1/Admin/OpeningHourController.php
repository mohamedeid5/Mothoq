<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\ServiceCenters\UpdateOpeningHoursAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceCenters\UpdateOpeningHoursRequest;
use App\Http\Resources\Api\V1\OpeningHourResource;
use App\Queries\ServiceCenters\AdminServiceCenterQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class OpeningHourController extends Controller
{
    public function update(
        UpdateOpeningHoursRequest $request,
        int $serviceCenter,
        AdminServiceCenterQuery $serviceCenters,
        UpdateOpeningHoursAction $updateOpeningHours,
    ): AnonymousResourceCollection {
        $serviceCenterModel = $serviceCenters->findOrFail($serviceCenter);
        Gate::authorize('update', $serviceCenterModel);

        $openingHours = $updateOpeningHours->handle($serviceCenterModel, $request->toData());

        return OpeningHourResource::collection($openingHours);
    }
}
