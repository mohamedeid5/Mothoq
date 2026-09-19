<?php

namespace App\Http\Controllers\Web\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\UpdateServiceCenterRequest;
use App\Queries\Catalog\PublicCatalogQuery;
use App\Queries\ServiceCenters\OwnerServiceCenterQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ServiceCenterController extends Controller
{
    public function __construct(private readonly OwnerServiceCenterQuery $serviceCenters) {}

    public function index(Request $request): View
    {
        return view('owner.dashboard', [
            'serviceCenters' => $this->serviceCenters->paginateFor($request->user()),
        ]);
    }

    public function edit(
        Request $request,
        int $serviceCenter,
        PublicCatalogQuery $catalog,
    ): View {
        $serviceCenterModel = $this->serviceCenters->findForOwnerOrFail(
            $request->user(),
            $serviceCenter,
        );
        Gate::authorize('update', $serviceCenterModel);

        return view('owner.service-centers.edit', [
            'serviceCenter' => $serviceCenterModel,
            'governorates' => $catalog->governoratesWithCities(),
        ]);
    }

    public function update(
        UpdateServiceCenterRequest $request,
        int $serviceCenter,
    ): RedirectResponse {
        $serviceCenterModel = $this->serviceCenters->findForOwnerOrFail(
            $request->user(),
            $serviceCenter,
        );
        Gate::authorize('update', $serviceCenterModel);

        $serviceCenterModel->update($request->toData()->toArray());

        return redirect()
            ->route('owner.service-centers.edit', $serviceCenterModel)
            ->with('success', 'تم حفظ بيانات المركز بنجاح.');
    }
}
