<?php

namespace App\Http\Controllers\Web\Owner;

use App\Actions\ServiceCenters\UpdateOpeningHoursAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceCenters\UpdateOpeningHoursRequest;
use App\Queries\ServiceCenters\OwnerServiceCenterQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class OpeningHourController extends Controller
{
    public function update(
        UpdateOpeningHoursRequest $request,
        int $serviceCenter,
        OwnerServiceCenterQuery $serviceCenters,
        UpdateOpeningHoursAction $updateOpeningHours,
    ): RedirectResponse {
        $serviceCenterModel = $serviceCenters->findForOwnerOrFail(
            $request->user(),
            $serviceCenter,
        );
        Gate::authorize('update', $serviceCenterModel);

        $updateOpeningHours->handle($serviceCenterModel, $request->toData());

        return redirect()
            ->route('owner.service-centers.edit', $serviceCenterModel)
            ->with('success', 'تم حفظ مواعيد العمل بنجاح.');
    }
}
