<?php

namespace App\Http\Controllers\Web\Admin;

use App\Actions\ServiceCenters\SetServiceCenterStatusAction;
use App\Actions\ServiceCenters\SetServiceCenterVerificationAction;
use App\Actions\ServiceCenters\UpdateServiceCenterAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexServiceCenterRequest;
use App\Http\Requests\Admin\UpdateServiceCenterStatusRequest;
use App\Http\Requests\Admin\UpdateServiceCenterVerificationRequest;
use App\Http\Requests\ServiceCenters\UpdateServiceCenterRequest;
use App\Models\ServiceCenter;
use App\Queries\Catalog\PublicCatalogQuery;
use App\Queries\ServiceCenters\AdminServiceCenterQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ServiceCenterController extends Controller
{
    public function __construct(private readonly AdminServiceCenterQuery $serviceCenters) {}

    public function index(IndexServiceCenterRequest $request): View
    {
        Gate::authorize('viewAny', ServiceCenter::class);

        return view('admin.service-centers.index', [
            'serviceCenters' => $this->serviceCenters->paginate(
                search: $request->search(),
                status: $request->status(),
                verified: $request->verified(),
            ),
        ]);
    }

    public function show(int $serviceCenter): View
    {
        $serviceCenterModel = $this->serviceCenters->findOrFail($serviceCenter);
        Gate::authorize('view', $serviceCenterModel);

        return view('admin.service-centers.show', [
            'serviceCenter' => $serviceCenterModel,
        ]);
    }

    public function edit(int $serviceCenter, PublicCatalogQuery $catalog): View
    {
        $serviceCenterModel = $this->serviceCenters->findOrFail($serviceCenter);
        Gate::authorize('update', $serviceCenterModel);

        return view('admin.service-centers.edit', [
            'serviceCenter' => $serviceCenterModel,
            'governorates' => $catalog->governoratesWithCities(),
            'services' => $catalog->services(),
            'carBrands' => $catalog->carBrands(),
        ]);
    }

    public function update(
        UpdateServiceCenterRequest $request,
        int $serviceCenter,
        UpdateServiceCenterAction $updateServiceCenter,
    ): RedirectResponse {
        $serviceCenterModel = $this->serviceCenters->findOrFail($serviceCenter);
        Gate::authorize('update', $serviceCenterModel);

        $updateServiceCenter->handle($serviceCenterModel, $request->toData());

        return redirect()
            ->route('admin.service-centers.edit', $serviceCenterModel)
            ->with('success', 'تم حفظ بيانات المركز بنجاح.');
    }

    public function updateStatus(
        UpdateServiceCenterStatusRequest $request,
        int $serviceCenter,
        SetServiceCenterStatusAction $setStatus,
    ): RedirectResponse {
        $serviceCenterModel = $this->serviceCenters->findOrFail($serviceCenter);
        Gate::authorize('publish', $serviceCenterModel);

        $setStatus->handle($serviceCenterModel, $request->status());

        return back()->with('success', 'تم تحديث حالة نشر المركز بنجاح.');
    }

    public function updateVerification(
        UpdateServiceCenterVerificationRequest $request,
        int $serviceCenter,
        SetServiceCenterVerificationAction $setVerification,
    ): RedirectResponse {
        $serviceCenterModel = $this->serviceCenters->findOrFail($serviceCenter);
        Gate::authorize('verify', $serviceCenterModel);

        $setVerification->handle($serviceCenterModel, $request->boolean('verified'));

        return back()->with('success', 'تم تحديث حالة توثيق المركز بنجاح.');
    }
}
