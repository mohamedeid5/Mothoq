<?php

namespace App\Queries\Reviews;

use App\Enums\ReviewStatus;
use App\Models\Review;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class AdminReviewQuery
{
    /** @return LengthAwarePaginator<int, Review> */
    public function paginate(?string $search, ?ReviewStatus $status): LengthAwarePaginator
    {
        return Review::query()
            ->with(['user:id,name,email', 'serviceCenter:id,name,slug'])
            ->when($search !== null, function (Builder $query) use ($search): Builder {
                $escapedSearch = addcslashes($search, '%_\\');

                return $query->where(function (Builder $searchQuery) use ($escapedSearch): void {
                    $searchQuery
                        ->where('comment', 'like', "%{$escapedSearch}%")
                        ->orWhereHas('user', fn (Builder $userQuery): Builder => $userQuery
                            ->where(fn (Builder $identityQuery): Builder => $identityQuery
                                ->where('name', 'like', "%{$escapedSearch}%")
                                ->orWhere('email', 'like', "%{$escapedSearch}%")))
                        ->orWhereHas('serviceCenter', fn (Builder $serviceCenterQuery): Builder => $serviceCenterQuery
                            ->where('name', 'like', "%{$escapedSearch}%"));
                });
            })
            ->when($status !== null, fn (Builder $query): Builder => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();
    }
}
