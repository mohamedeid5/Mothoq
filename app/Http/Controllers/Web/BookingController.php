<?php

namespace App\Http\Controllers\Web;

use App\Actions\Bookings\CancelBookingAction;
use App\Actions\Bookings\CreateBookingAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bookings\StoreBookingRequest;
use App\Models\Booking;
use App\Models\User;
use App\Queries\Bookings\CustomerBookingQuery;
use App\Queries\ServiceCenters\PublicServiceCenterQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BookingController extends Controller
{
    public function __construct(private readonly CustomerBookingQuery $bookings) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Booking::class);

        /** @var User $customer */
        $customer = $request->user();

        return view('bookings.index', [
            'bookings' => $this->bookings->paginateFor($customer),
        ]);
    }

    public function store(
        StoreBookingRequest $request,
        string $serviceCenter,
        PublicServiceCenterQuery $serviceCenters,
        CreateBookingAction $createBooking,
    ): RedirectResponse {
        $serviceCenterModel = $serviceCenters->findBySlugOrFail($serviceCenter);
        Gate::authorize('create', [Booking::class, $serviceCenterModel]);

        /** @var User $customer */
        $customer = $request->user();
        $createBooking->handle($customer, $serviceCenterModel, $request->toData());

        return redirect()
            ->route('bookings.index')
            ->with('success', 'تم إرسال طلب الحجز إلى المركز بنجاح.');
    }

    public function destroy(Request $request, int $booking, CancelBookingAction $cancelBooking): RedirectResponse
    {
        /** @var User $customer */
        $customer = $request->user();
        $bookingModel = $this->bookings->findForCustomerOrFail($customer, $booking);
        Gate::authorize('cancel', $bookingModel);

        $cancelBooking->handle($bookingModel);

        return back()->with('success', 'تم إلغاء الحجز بنجاح.');
    }
}
