<?php

namespace App\Http\Controllers\Web\Admin;

use App\Actions\ServiceCenters\UpdateBookingCapacityAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceCenters\UpdateBookingCapacityRequest;
use App\Queries\ServiceCenters\AdminServiceCenterQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class BookingCapacityController extends Controller
{
    public function update(UpdateBookingCapacityRequest $request, int $serviceCenter, AdminServiceCenterQuery $centers, UpdateBookingCapacityAction $update): RedirectResponse
    {
        $center = $centers->findOrFail($serviceCenter);
        Gate::authorize('update', $center);
        $update->handle($center, $request->integer('booking_capacity'));

        return redirect()->route('admin.service-centers.edit', $center)->with('success', 'تم حفظ سعة الحجوزات بنجاح.');
    }
}
