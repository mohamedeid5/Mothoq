<?php

namespace App\Http\Controllers\Web\Admin;

use App\Actions\ServiceCenters\DeleteCenterImageAction;
use App\Actions\ServiceCenters\ReorderCenterImagesAction;
use App\Actions\ServiceCenters\SetCenterCoverImageAction;
use App\Actions\ServiceCenters\StoreCenterImageAction;
use App\Actions\ServiceCenters\UpdateCenterImageAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceCenters\ReorderCenterImagesRequest;
use App\Http\Requests\ServiceCenters\StoreCenterImageRequest;
use App\Http\Requests\ServiceCenters\UpdateCenterImageRequest;
use App\Models\CenterImage;
use App\Models\ServiceCenter;
use App\Queries\ServiceCenters\AdminServiceCenterQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CenterImageController extends Controller
{
    public function __construct(private readonly AdminServiceCenterQuery $serviceCenters) {}

    public function store(StoreCenterImageRequest $request, int $serviceCenter, StoreCenterImageAction $storeImage): RedirectResponse
    {
        $serviceCenterModel = $this->serviceCenters->findOrFail($serviceCenter);
        Gate::authorize('create', [CenterImage::class, $serviceCenterModel]);

        $storeImage->handle($serviceCenterModel, $request->toData());

        return $this->redirectToEdit($serviceCenterModel, 'تمت إضافة الصورة بنجاح.');
    }

    public function update(UpdateCenterImageRequest $request, int $serviceCenter, int $centerImage, UpdateCenterImageAction $updateImage): RedirectResponse
    {
        $serviceCenterModel = $this->serviceCenters->findOrFail($serviceCenter);
        $centerImageModel = $this->findImage($serviceCenterModel, $centerImage);
        Gate::authorize('update', $centerImageModel);

        $updateImage->handle($centerImageModel, $request->toData());

        return $this->redirectToEdit($serviceCenterModel, 'تم تحديث وصف الصورة بنجاح.');
    }

    public function cover(Request $request, int $serviceCenter, int $centerImage, SetCenterCoverImageAction $setCoverImage): RedirectResponse
    {
        $serviceCenterModel = $this->serviceCenters->findOrFail($serviceCenter);
        $centerImageModel = $this->findImage($serviceCenterModel, $centerImage);
        Gate::authorize('update', $centerImageModel);

        $setCoverImage->handle($centerImageModel);

        return $this->redirectToEdit($serviceCenterModel, 'تم تعيين صورة الغلاف بنجاح.');
    }

    public function reorder(ReorderCenterImagesRequest $request, int $serviceCenter, ReorderCenterImagesAction $reorderImages): RedirectResponse
    {
        $serviceCenterModel = $this->serviceCenters->findOrFail($serviceCenter);
        Gate::authorize('reorder', [CenterImage::class, $serviceCenterModel]);

        $reorderImages->handle($serviceCenterModel, $request->toData());

        return $this->redirectToEdit($serviceCenterModel, 'تم حفظ ترتيب الصور بنجاح.');
    }

    public function destroy(Request $request, int $serviceCenter, int $centerImage, DeleteCenterImageAction $deleteImage): RedirectResponse
    {
        $serviceCenterModel = $this->serviceCenters->findOrFail($serviceCenter);
        $centerImageModel = $this->findImage($serviceCenterModel, $centerImage);
        Gate::authorize('delete', $centerImageModel);

        $deleteImage->handle($centerImageModel);

        return $this->redirectToEdit($serviceCenterModel, 'تم حذف الصورة بنجاح.');
    }

    private function findImage(ServiceCenter $serviceCenter, int $centerImage): CenterImage
    {
        return $serviceCenter->images()->findOrFail($centerImage);
    }

    private function redirectToEdit(ServiceCenter $serviceCenter, string $message): RedirectResponse
    {
        return redirect()
            ->route('admin.service-centers.edit', $serviceCenter)
            ->with('success', $message);
    }
}
