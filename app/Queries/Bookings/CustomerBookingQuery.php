<?php

namespace App\Queries\Bookings;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class CustomerBookingQuery
{
    /** @return LengthAwarePaginator<int, Booking> */
    public function paginateFor(User $customer): LengthAwarePaginator
    {
        return $customer->bookings()
            ->with($this->relations())
            ->orderByDesc('scheduled_at')
            ->orderByDesc('id')
            ->paginate(15);
    }

    public function findForCustomerOrFail(User $customer, int $bookingId): Booking
    {
        return $customer->bookings()
            ->with($this->relations())
            ->findOrFail($bookingId);
    }

    /** @return list<string> */
    private function relations(): array
    {
        return [
            'customer:id,name,email',
            'serviceCenter:id,name,slug,timezone,owner_id,status',
            'service:id,name,slug',
        ];
    }
}
