<?php

namespace App\Queries\Bookings;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class OwnerBookingQuery
{
    /** @return LengthAwarePaginator<int, Booking> */
    public function paginateFor(
        User $owner,
        ?string $search,
        ?BookingStatus $status,
        ?string $date,
    ): LengthAwarePaginator {
        return $this->baseQuery($owner)
            ->when($search !== null, function (Builder $query) use ($search): Builder {
                $escapedSearch = addcslashes($search, '%_\\');

                return $query->where(function (Builder $searchQuery) use ($escapedSearch): void {
                    $searchQuery
                        ->where('customer_phone', 'like', "%{$escapedSearch}%")
                        ->orWhereHas('customer', fn (Builder $customerQuery): Builder => $customerQuery
                            ->where('name', 'like', "%{$escapedSearch}%"))
                        ->orWhereHas('service', fn (Builder $serviceQuery): Builder => $serviceQuery
                            ->where('name', 'like', "%{$escapedSearch}%"));
                });
            })
            ->when($status !== null, fn (Builder $query): Builder => $query->where('status', $status))
            ->when($date !== null, fn (Builder $query): Builder => $query->whereDate('scheduled_at', $date))
            ->orderByDesc('scheduled_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();
    }

    public function findForOwnerOrFail(User $owner, int $bookingId): Booking
    {
        return $this->baseQuery($owner)->findOrFail($bookingId);
    }

    private function baseQuery(User $owner): Builder
    {
        return Booking::query()
            ->with([
                'customer:id,name,email',
                'serviceCenter:id,name,slug,owner_id',
                'service:id,name,slug',
            ])
            ->whereHas('serviceCenter', fn (Builder $query): Builder => $query
                ->whereBelongsTo($owner, 'owner'));
    }
}
