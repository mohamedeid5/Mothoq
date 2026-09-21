<?php

namespace App\Http\Controllers\Web;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Queries\ServiceCenters\PublicServiceCenterQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ServiceCenterController extends Controller
{
    public function __invoke(
        Request $request,
        string $serviceCenter,
        PublicServiceCenterQuery $serviceCenters,
    ): View {
        $serviceCenterModel = $serviceCenters->findBySlugOrFail($serviceCenter);
        $viewer = $request->user();

        return view('service-centers.show', [
            'serviceCenter' => $serviceCenterModel,
            'viewerReview' => $viewer?->role === UserRole::Customer
                ? $viewer->reviews()->whereBelongsTo($serviceCenterModel)->first()
                : null,
        ]);
    }
}
