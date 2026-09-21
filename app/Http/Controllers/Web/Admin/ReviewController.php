<?php

namespace App\Http\Controllers\Web\Admin;

use App\Actions\Reviews\SetReviewStatusAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexReviewRequest;
use App\Http\Requests\Admin\UpdateReviewStatusRequest;
use App\Models\Review;
use App\Queries\Reviews\AdminReviewQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ReviewController extends Controller
{
    public function __construct(private readonly AdminReviewQuery $reviews) {}

    public function index(IndexReviewRequest $request): View
    {
        Gate::authorize('viewAny', Review::class);

        return view('admin.reviews.index', [
            'reviews' => $this->reviews->paginate(
                search: $request->search(),
                status: $request->status(),
            ),
        ]);
    }

    public function updateStatus(
        UpdateReviewStatusRequest $request,
        int $review,
        SetReviewStatusAction $setStatus,
    ): RedirectResponse {
        $reviewModel = Review::query()->findOrFail($review);
        Gate::authorize('moderate', $reviewModel);

        $setStatus->handle($reviewModel, $request->status());

        return back()->with('success', 'تم تحديث حالة التقييم بنجاح.');
    }
}
