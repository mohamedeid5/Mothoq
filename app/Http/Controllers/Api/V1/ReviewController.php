<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Reviews\CreateReviewAction;
use App\Actions\Reviews\DeleteReviewAction;
use App\Actions\Reviews\UpdateReviewAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reviews\StoreReviewRequest;
use App\Http\Requests\Reviews\UpdateReviewRequest;
use App\Http\Resources\Api\V1\ReviewResource;
use App\Models\Review;
use App\Models\User;
use App\Queries\ServiceCenters\PublicServiceCenterQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ReviewController extends Controller
{
    public function store(
        StoreReviewRequest $request,
        string $serviceCenter,
        PublicServiceCenterQuery $serviceCenters,
        CreateReviewAction $createReview,
    ): JsonResponse {
        $serviceCenterModel = $serviceCenters->findBySlugOrFail($serviceCenter);
        Gate::authorize('create', [Review::class, $serviceCenterModel]);

        /** @var User $customer */
        $customer = $request->user();
        $review = $createReview->handle($customer, $serviceCenterModel, $request->toData());

        return (new ReviewResource($review))
            ->response()
            ->setStatusCode(201);
    }

    public function update(
        UpdateReviewRequest $request,
        int $review,
        UpdateReviewAction $updateReview,
    ): ReviewResource {
        /** @var User $customer */
        $customer = $request->user();
        $reviewModel = $this->findForCustomer($customer, $review);
        Gate::authorize('update', $reviewModel);

        return new ReviewResource(
            $updateReview->handle($reviewModel, $request->toData()),
        );
    }

    public function destroy(Request $request, int $review, DeleteReviewAction $deleteReview): Response
    {
        /** @var User $customer */
        $customer = $request->user();
        $reviewModel = $this->findForCustomer($customer, $review);
        Gate::authorize('delete', $reviewModel);

        $deleteReview->handle($reviewModel);

        return response()->noContent();
    }

    private function findForCustomer(User $customer, int $review): Review
    {
        return $customer->reviews()->findOrFail($review);
    }
}
