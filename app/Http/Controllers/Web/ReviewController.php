<?php

namespace App\Http\Controllers\Web;

use App\Actions\Reviews\CreateReviewAction;
use App\Actions\Reviews\DeleteReviewAction;
use App\Actions\Reviews\UpdateReviewAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reviews\StoreReviewRequest;
use App\Http\Requests\Reviews\UpdateReviewRequest;
use App\Models\Review;
use App\Models\User;
use App\Queries\ServiceCenters\PublicServiceCenterQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReviewController extends Controller
{
    public function store(
        StoreReviewRequest $request,
        string $serviceCenter,
        PublicServiceCenterQuery $serviceCenters,
        CreateReviewAction $createReview,
    ): RedirectResponse {
        $serviceCenterModel = $serviceCenters->findBySlugOrFail($serviceCenter);
        Gate::authorize('create', [Review::class, $serviceCenterModel]);

        /** @var User $customer */
        $customer = $request->user();
        $createReview->handle($customer, $serviceCenterModel, $request->toData());

        return $this->redirectToCenter($serviceCenterModel->slug, 'تم إرسال تقييمك للمراجعة بنجاح.');
    }

    public function update(
        UpdateReviewRequest $request,
        int $review,
        UpdateReviewAction $updateReview,
    ): RedirectResponse {
        /** @var User $customer */
        $customer = $request->user();
        $reviewModel = $this->findForCustomer($customer, $review);
        Gate::authorize('update', $reviewModel);

        $updatedReview = $updateReview->handle($reviewModel, $request->toData());

        return $this->redirectToCenter($updatedReview->serviceCenter->slug, 'تم تحديث تقييمك وإرساله للمراجعة.');
    }

    public function destroy(
        Request $request,
        int $review,
        DeleteReviewAction $deleteReview,
    ): RedirectResponse {
        /** @var User $customer */
        $customer = $request->user();
        $reviewModel = $this->findForCustomer($customer, $review);
        Gate::authorize('delete', $reviewModel);
        $serviceCenterSlug = $reviewModel->serviceCenter->slug;

        $deleteReview->handle($reviewModel);

        return $this->redirectToCenter($serviceCenterSlug, 'تم حذف تقييمك بنجاح.');
    }

    private function findForCustomer(User $customer, int $review): Review
    {
        return $customer->reviews()
            ->with('serviceCenter:id,slug')
            ->findOrFail($review);
    }

    private function redirectToCenter(string $slug, string $message): RedirectResponse
    {
        return redirect()
            ->route('service-centers.show', $slug)
            ->with('success', $message);
    }
}
