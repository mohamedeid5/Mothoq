<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Bookings\CancelBookingAction;
use App\Actions\Bookings\CreateBookingAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bookings\StoreBookingRequest;
use App\Http\Resources\Api\V1\BookingResource;
use App\Models\Booking;
use App\Models\User;
use App\Queries\Bookings\CustomerBookingQuery;
use App\Queries\ServiceCenters\PublicServiceCenterQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class BookingController extends Controller
{
    public function __construct(private readonly CustomerBookingQuery $bookings) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Booking::class);

        /** @var User $customer */
        $customer = $request->user();

        return BookingResource::collection($this->bookings->paginateFor($customer));
    }

    public function store(
        StoreBookingRequest $request,
        string $serviceCenter,
        PublicServiceCenterQuery $serviceCenters,
        CreateBookingAction $createBooking,
    ): JsonResponse {
        $serviceCenterModel = $serviceCenters->findBySlugOrFail($serviceCenter);
        Gate::authorize('create', [Booking::class, $serviceCenterModel]);

        /** @var User $customer */
        $customer = $request->user();
        $booking = $createBooking->handle($customer, $serviceCenterModel, $request->toData());

        return (new BookingResource($booking))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Request $request, int $booking, CancelBookingAction $cancelBooking): BookingResource
    {
        /** @var User $customer */
        $customer = $request->user();
        $bookingModel = $this->bookings->findForCustomerOrFail($customer, $booking);
        Gate::authorize('cancel', $bookingModel);

        return new BookingResource($cancelBooking->handle($bookingModel));
    }
}
