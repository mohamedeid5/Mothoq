<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\ServiceCenters\ResolveScheduleAction;
use App\Http\Controllers\Controller;
use App\Queries\ServiceCenters\PublicServiceCenterQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function show(Request $request, string $serviceCenter, PublicServiceCenterQuery $centers, ResolveScheduleAction $schedules): JsonResponse
    {
        $data = $request->validate(['date' => ['required', 'date_format:Y-m-d']]);
        $center = $centers->findBySlugOrFail($serviceCenter);

        return response()->json(['data' => $schedules->handle($center, $data['date'])]);
    }
}
