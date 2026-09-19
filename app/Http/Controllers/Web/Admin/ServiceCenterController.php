<?php

namespace App\Http\Controllers\Web\Admin;

use App\Actions\ServiceCenters\SetServiceCenterStatusAction;
use App\Actions\ServiceCenters\SetServiceCenterVerificationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Admin\IndexServiceCenterRequest;
use App\Http\Requests\Web\Admin\UpdateServiceCenterStatusRequest;
use App\Http\Requests\Web\Admin\UpdateServiceCenterVerificationRequest;
use App\Models\ServiceCenter;
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
