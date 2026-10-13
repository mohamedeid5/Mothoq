<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\ServiceCenters\UpdateBookingCapacityAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceCenters\UpdateBookingCapacityRequest;
use App\Http\Resources\Api\V1\AdminServiceCenterResource;
use App\Queries\ServiceCenters\AdminServiceCenterQuery;
use Illuminate\Support\Facades\Gate;

class BookingCapacityController extends Controller
{
    public function update(UpdateBookingCapacityRequest $request, int $serviceCenter, AdminServiceCenterQuery $centers, UpdateBookingCapacityAction $update): AdminServiceCenterResource
    {
        $center = $centers->findOrFail($serviceCenter);
        Gate::authorize('update', $center);

        $updated = $update->handle($center, $request->integer('booking_capacity'));
        $updated->load(['owner', 'city.governorate', 'services', 'carBrands', 'openingHours']);

        return new AdminServiceCenterResource($updated);
    }
}
