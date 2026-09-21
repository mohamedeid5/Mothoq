<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Reviews\SetReviewStatusAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexReviewRequest;
use App\Http\Requests\Admin\UpdateReviewStatusRequest;
use App\Http\Resources\Api\V1\ReviewResource;
use App\Models\Review;
use App\Queries\Reviews\AdminReviewQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class ReviewController extends Controller
{
    public function __construct(private readonly AdminReviewQuery $reviews) {}

    public function index(IndexReviewRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Review::class);

        return ReviewResource::collection(
            $this->reviews->paginate(
                search: $request->search(),
                status: $request->status(),
            ),
        );
    }

    public function updateStatus(
        UpdateReviewStatusRequest $request,
        int $review,
        SetReviewStatusAction $setStatus,
    ): ReviewResource {
        $reviewModel = Review::query()->findOrFail($review);
        Gate::authorize('moderate', $reviewModel);

        return new ReviewResource(
            $setStatus->handle($reviewModel, $request->status()),
        );
    }
}
