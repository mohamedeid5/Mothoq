<?php

namespace App\Http\Controllers\Web\Admin;

use App\Actions\ServiceCenters\UpdateOpeningHoursAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceCenters\UpdateOpeningHoursRequest;
use App\Queries\ServiceCenters\AdminServiceCenterQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class OpeningHourController extends Controller
{
    public function update(
        UpdateOpeningHoursRequest $request,
        int $serviceCenter,
        AdminServiceCenterQuery $serviceCenters,
        UpdateOpeningHoursAction $updateOpeningHours,
    ): RedirectResponse {
        $serviceCenterModel = $serviceCenters->findOrFail($serviceCenter);
        Gate::authorize('update', $serviceCenterModel);

        $updateOpeningHours->handle($serviceCenterModel, $request->toData());

        return redirect()
            ->route('admin.service-centers.edit', $serviceCenterModel)
            ->with('success', 'تم حفظ مواعيد العمل بنجاح.');
    }
}
