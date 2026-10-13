<?php

namespace App\Http\Controllers\Web\Owner;

use App\Actions\ServiceCenters\UpdateBookingCapacityAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceCenters\UpdateBookingCapacityRequest;
use App\Queries\ServiceCenters\OwnerServiceCenterQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class BookingCapacityController extends Controller
{
    public function update(UpdateBookingCapacityRequest $request, int $serviceCenter, OwnerServiceCenterQuery $centers, UpdateBookingCapacityAction $update): RedirectResponse
    {
        $center = $centers->findForOwnerOrFail($request->user(), $serviceCenter);
        Gate::authorize('update', $center);
        $update->handle($center, $request->integer('booking_capacity'));

        return redirect()->route('owner.service-centers.edit', $center)->with('success', 'تم حفظ سعة الحجوزات بنجاح.');
    }
}
