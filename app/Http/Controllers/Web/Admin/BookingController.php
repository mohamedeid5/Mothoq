<?php

namespace App\Http\Controllers\Web\Admin;

use App\Actions\Bookings\RescheduleBookingAction;
use App\Actions\Bookings\UpdateBookingStatusAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bookings\IndexBookingRequest;
use App\Http\Requests\Bookings\RescheduleBookingRequest;
use App\Http\Requests\Bookings\UpdateBookingStatusRequest;
use App\Models\Booking;
use App\Queries\Bookings\AdminBookingQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class BookingController extends Controller
{
    public function __construct(private readonly AdminBookingQuery $bookings) {}

    public function index(IndexBookingRequest $request): View
    {
        Gate::authorize('viewAny', Booking::class);

        return view('admin.bookings.index', [
            'bookings' => $this->bookings->paginate(
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
        $bookingModel = $this->bookings->findOrFail($booking);
        Gate::authorize('updateStatus', $bookingModel);

        $updateStatus->handle($bookingModel, $request->toData());

        return back()->with('success', 'تم تحديث حالة الحجز بنجاح.');
    }

    public function reschedule(RescheduleBookingRequest $request, int $booking, RescheduleBookingAction $rescheduleBooking): RedirectResponse
    {
        $bookingModel = $this->bookings->findOrFail($booking);
        Gate::authorize('reschedule', $bookingModel);

        $rescheduleBooking->handle($bookingModel, $request->string('scheduled_at')->toString());

        return back()->with('success', 'تم تغيير موعد الحجز بنجاح.');
    }
}
