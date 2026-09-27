<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Bookings\UpdateBookingStatusAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bookings\IndexBookingRequest;
use App\Http\Requests\Bookings\UpdateBookingStatusRequest;
use App\Http\Resources\Api\V1\BookingResource;
use App\Models\Booking;
use App\Queries\Bookings\AdminBookingQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class BookingController extends Controller
{
    public function __construct(private readonly AdminBookingQuery $bookings) {}

    public function index(IndexBookingRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Booking::class);

        return BookingResource::collection(
            $this->bookings->paginate(
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
        $bookingModel = $this->bookings->findOrFail($booking);
        Gate::authorize('updateStatus', $bookingModel);

        return new BookingResource(
            $updateStatus->handle($bookingModel, $request->toData()),
        );
    }
}
