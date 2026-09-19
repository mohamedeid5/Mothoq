<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Queries\ServiceCenters\PublicServiceCenterQuery;
use Illuminate\Contracts\View\View;

class ServiceCenterController extends Controller
{
    public function __invoke(
        string $serviceCenter,
        PublicServiceCenterQuery $serviceCenters,
    ): View {
        return view('service-centers.show', [
            'serviceCenter' => $serviceCenters->findBySlugOrFail($serviceCenter),
        ]);
    }
}
