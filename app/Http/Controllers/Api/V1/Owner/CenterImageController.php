<?php

namespace App\Http\Controllers\Api\V1\Owner;

use App\Actions\ServiceCenters\DeleteCenterImageAction;
use App\Actions\ServiceCenters\ReorderCenterImagesAction;
use App\Actions\ServiceCenters\SetCenterCoverImageAction;
use App\Actions\ServiceCenters\StoreCenterImageAction;
use App\Actions\ServiceCenters\UpdateCenterImageAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceCenters\ReorderCenterImagesRequest;
use App\Http\Requests\ServiceCenters\StoreCenterImageRequest;
use App\Http\Requests\ServiceCenters\UpdateCenterImageRequest;
use App\Http\Resources\Api\V1\CenterImageResource;
use App\Models\CenterImage;
use App\Models\ServiceCenter;
use App\Queries\ServiceCenters\OwnerServiceCenterQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class CenterImageController extends Controller
{
    public function __construct(private readonly OwnerServiceCenterQuery $serviceCenters) {}

    public function store(StoreCenterImageRequest $request, int $serviceCenter, StoreCenterImageAction $storeImage): JsonResponse
    {
        $serviceCenterModel = $this->findServiceCenter($request, $serviceCenter);
        Gate::authorize('create', [CenterImage::class, $serviceCenterModel]);

        $centerImage = $storeImage->handle($serviceCenterModel, $request->toData());

        return (new CenterImageResource($centerImage))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateCenterImageRequest $request, int $serviceCenter, int $centerImage, UpdateCenterImageAction $updateImage): CenterImageResource
    {
        $serviceCenterModel = $this->findServiceCenter($request, $serviceCenter);
        $centerImageModel = $this->findImage($serviceCenterModel, $centerImage);
        Gate::authorize('update', $centerImageModel);

        return new CenterImageResource(
            $updateImage->handle($centerImageModel, $request->toData()),
        );
    }

    public function cover(Request $request, int $serviceCenter, int $centerImage, SetCenterCoverImageAction $setCoverImage): CenterImageResource
    {
        $serviceCenterModel = $this->findServiceCenter($request, $serviceCenter);
        $centerImageModel = $this->findImage($serviceCenterModel, $centerImage);
        Gate::authorize('update', $centerImageModel);

        return new CenterImageResource($setCoverImage->handle($centerImageModel));
    }

    public function reorder(ReorderCenterImagesRequest $request, int $serviceCenter, ReorderCenterImagesAction $reorderImages): AnonymousResourceCollection
    {
        $serviceCenterModel = $this->findServiceCenter($request, $serviceCenter);
        Gate::authorize('reorder', [CenterImage::class, $serviceCenterModel]);

        return CenterImageResource::collection(
            $reorderImages->handle($serviceCenterModel, $request->toData()),
        );
    }

    public function destroy(Request $request, int $serviceCenter, int $centerImage, DeleteCenterImageAction $deleteImage): Response
    {
        $serviceCenterModel = $this->findServiceCenter($request, $serviceCenter);
        $centerImageModel = $this->findImage($serviceCenterModel, $centerImage);
        Gate::authorize('delete', $centerImageModel);

        $deleteImage->handle($centerImageModel);

        return response()->noContent();
    }

    private function findServiceCenter(Request $request, int $serviceCenter): ServiceCenter
    {
        return $this->serviceCenters->findForOwnerOrFail($request->user(), $serviceCenter);
    }

    private function findImage(ServiceCenter $serviceCenter, int $centerImage): CenterImage
    {
        return $serviceCenter->images()->findOrFail($centerImage);
    }
}
