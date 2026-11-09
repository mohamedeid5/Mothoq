<?php

namespace App\Http\Controllers\Web\Owner;

use App\Actions\ServiceCenters\UpdateServiceDurationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceCenters\UpdateServiceDurationRequest;
use App\Queries\ServiceCenters\OwnerServiceCenterQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ServiceDurationController extends Controller
{
    public function update(UpdateServiceDurationRequest $request, int $serviceCenter, int $service, OwnerServiceCenterQuery $centers, UpdateServiceDurationAction $update): RedirectResponse
    {
        $center = $centers->findForOwnerOrFail($request->user(), $serviceCenter);
        Gate::authorize('update', $center);
        $duration = $request->validated('duration_minutes');
        $update->handle($center, $service, $duration === null ? null : (int) $duration);

        return redirect()->route('owner.service-centers.edit', $center)->with('success', 'تم حفظ مدة الخدمة بنجاح.');
    }
}
