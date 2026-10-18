<?php

namespace App\Http\Controllers\Web;

use App\Actions\Bookings\CancelBookingAction;
use App\Actions\Bookings\CreateIdempotentBookingAction;
use App\Actions\Bookings\RescheduleBookingAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bookings\RescheduleBookingRequest;
use App\Http\Requests\Bookings\StoreBookingRequest;
use App\Models\Booking;
use App\Models\ServiceCenter;
use App\Models\User;
use App\Queries\Bookings\CustomerBookingQuery;
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
        CreateIdempotentBookingAction $createBooking,
    ): RedirectResponse {
        $serviceCenterModel = ServiceCenter::withTrashed()->where('slug', $serviceCenter)->firstOrFail();

        /** @var User $customer */
        $customer = $request->user();
        $result = $createBooking->handle($customer, $serviceCenterModel, $request->toData(), $request->idempotencyKey());

        if ($result->getStatusCode() !== 201) {
            $message = $result->getData(true)['message'] ?? 'تعذر إكمال طلب الحجز. حاول مجددًا.';

            return back()->withErrors(['idempotency_key' => $message])
                ->withInput($result->getStatusCode() === 503 ? $request->all() : $request->except('idempotency_key'));
        }

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

    public function reschedule(RescheduleBookingRequest $request, int $booking, RescheduleBookingAction $rescheduleBooking): RedirectResponse
    {
        /** @var User $customer */
        $customer = $request->user();
        $bookingModel = $this->bookings->findForCustomerOrFail($customer, $booking);
        Gate::authorize('reschedule', $bookingModel);

        $rescheduleBooking->handle($bookingModel, $request->string('scheduled_at')->toString());

        return back()->with('success', 'تم تغيير موعد الحجز بنجاح.');
    }
}
