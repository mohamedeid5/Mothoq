<?php

namespace App\Http\Controllers\Api\V1\Owner;

use App\Actions\Bookings\UpdateBookingStatusAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bookings\IndexBookingRequest;
use App\Http\Requests\Bookings\UpdateBookingStatusRequest;
use App\Http\Resources\Api\V1\BookingResource;
use App\Models\Booking;
use App\Models\User;
use App\Queries\Bookings\OwnerBookingQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class BookingController extends Controller
{
    public function __construct(private readonly OwnerBookingQuery $bookings) {}

    public function index(IndexBookingRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Booking::class);

        /** @var User $owner */
        $owner = $request->user();

        return BookingResource::collection(
            $this->bookings->paginateFor(
                owner: $owner,
                search: $request->search(),
                status: $request->status(),
                date: $request->scheduledDate(),
            ),
        );
    }

    public function update(
        UpdateBookingStatusRequest $request,
        int $booking,
        UpdateBookingStatusAction $updateStatus,
    ): BookingResource {
        /** @var User $owner */
        $owner = $request->user();
        $bookingModel = $this->bookings->findForOwnerOrFail($owner, $booking);
        Gate::authorize('updateStatus', $bookingModel);

        return new BookingResource(
            $updateStatus->handle($bookingModel, $request->toData()),
        );
    }
}
