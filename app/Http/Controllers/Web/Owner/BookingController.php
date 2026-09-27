<?php

namespace App\Http\Controllers\Web\Owner;

use App\Actions\Bookings\UpdateBookingStatusAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bookings\IndexBookingRequest;
use App\Http\Requests\Bookings\UpdateBookingStatusRequest;
use App\Models\Booking;
use App\Models\User;
use App\Queries\Bookings\OwnerBookingQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class BookingController extends Controller
{
    public function __construct(private readonly OwnerBookingQuery $bookings) {}

    public function index(IndexBookingRequest $request): View
    {
        Gate::authorize('viewAny', Booking::class);

        /** @var User $owner */
        $owner = $request->user();

        return view('owner.bookings.index', [
            'bookings' => $this->bookings->paginateFor(
                owner: $owner,
                search: $request->search(),
                status: $request->status(),
                date: $request->scheduledDate(),
            ),
        ]);
    }

    public function update(
        UpdateBookingStatusRequest $request,
        int $booking,
        UpdateBookingStatusAction $updateStatus,
    ): RedirectResponse {
        /** @var User $owner */
        $owner = $request->user();
        $bookingModel = $this->bookings->findForOwnerOrFail($owner, $booking);
        Gate::authorize('updateStatus', $bookingModel);

        $updateStatus->handle($bookingModel, $request->toData());

        return back()->with('success', 'تم تحديث حالة الحجز بنجاح.');
    }
}
