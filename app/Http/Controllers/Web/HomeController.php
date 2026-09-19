<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceCenters\IndexServiceCenterRequest;
use App\Queries\Catalog\PublicCatalogQuery;
use App\Queries\ServiceCenters\PublicServiceCenterQuery;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(
        IndexServiceCenterRequest $request,
        PublicServiceCenterQuery $serviceCenters,
        PublicCatalogQuery $catalog,
    ): View {
        return view('home', [
            'serviceCenters' => $serviceCenters
                ->paginate($request->toFilters(defaultPerPage: 9))
                ->withQueryString(),
            'governorates' => $catalog->governoratesWithCities(),
            'services' => $catalog->services(),
            'carBrands' => $catalog->carBrands(),
        ]);
    }
}
