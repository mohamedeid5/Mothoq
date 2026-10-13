<?php

namespace App\Http\Controllers\Api\V1\Owner;

use App\Actions\ServiceCenters\UpdateBookingCapacityAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceCenters\UpdateBookingCapacityRequest;
use App\Http\Resources\Api\V1\OwnerServiceCenterResource;
use App\Queries\ServiceCenters\OwnerServiceCenterQuery;
use Illuminate\Support\Facades\Gate;

class BookingCapacityController extends Controller
{
    public function update(UpdateBookingCapacityRequest $request, int $serviceCenter, OwnerServiceCenterQuery $centers, UpdateBookingCapacityAction $update): OwnerServiceCenterResource
    {
        $center = $centers->findForOwnerOrFail($request->user(), $serviceCenter);
        Gate::authorize('update', $center);

        $updated = $update->handle($center, $request->integer('booking_capacity'));
        $updated->load(['owner', 'city.governorate', 'services', 'carBrands', 'openingHours']);

        return new OwnerServiceCenterResource($updated);
    }
}
