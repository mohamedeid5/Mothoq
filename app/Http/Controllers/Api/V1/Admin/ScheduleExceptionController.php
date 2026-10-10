<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\ServiceCenters\DeleteScheduleExceptionAction;
use App\Actions\ServiceCenters\UpdateScheduleExceptionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceCenters\StoreScheduleExceptionRequest;
use App\Http\Resources\Api\V1\ScheduleExceptionResource;
use App\Queries\ServiceCenters\AdminServiceCenterQuery;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ScheduleExceptionController extends Controller
{
    public function index(Request $request, int $serviceCenter, AdminServiceCenterQuery $centers): AnonymousResourceCollection
    {
        $center = $centers->findOrFail($serviceCenter);
        Gate::authorize('update', $center);

        return ScheduleExceptionResource::collection($center->scheduleExceptions()->orderBy('date')->paginate(31));
    }

    public function store(StoreScheduleExceptionRequest $request, int $serviceCenter, AdminServiceCenterQuery $centers, UpdateScheduleExceptionAction $update): ScheduleExceptionResource
    {
        $center = $centers->findOrFail($serviceCenter);
        Gate::authorize('update', $center);

        return new ScheduleExceptionResource($update->handle($center, $request->validated()));
    }

    public function destroy(Request $request, int $serviceCenter, int $scheduleException, AdminServiceCenterQuery $centers, DeleteScheduleExceptionAction $delete): Response
    {
        $center = $centers->findOrFail($serviceCenter);
        Gate::authorize('update', $center);
        $delete->handle($center, $scheduleException);

        return response()->noContent();
    }
}
