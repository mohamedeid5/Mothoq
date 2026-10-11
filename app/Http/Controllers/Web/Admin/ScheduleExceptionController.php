<?php

namespace App\Http\Controllers\Web\Admin;

use App\Actions\ServiceCenters\DeleteScheduleExceptionAction;
use App\Actions\ServiceCenters\UpdateScheduleExceptionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceCenters\StoreScheduleExceptionRequest;
use App\Queries\ServiceCenters\AdminServiceCenterQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ScheduleExceptionController extends Controller
{
    public function store(StoreScheduleExceptionRequest $request, int $serviceCenter, AdminServiceCenterQuery $centers, UpdateScheduleExceptionAction $update): RedirectResponse
    {
        $center = $centers->findOrFail($serviceCenter);
        Gate::authorize('update', $center);
        $update->handle($center, $request->validated());

        return redirect()->route('admin.service-centers.edit', $center)->with('success', 'تم حفظ الموعد الاستثنائي بنجاح.');
    }

    public function destroy(Request $request, int $serviceCenter, int $scheduleException, AdminServiceCenterQuery $centers, DeleteScheduleExceptionAction $delete): RedirectResponse
    {
        $center = $centers->findOrFail($serviceCenter);
        Gate::authorize('update', $center);
        $delete->handle($center, $scheduleException);

        return redirect()->route('admin.service-centers.edit', $center)->with('success', 'تم حذف الموعد الاستثنائي والعودة للمواعيد الأسبوعية.');
    }
}
