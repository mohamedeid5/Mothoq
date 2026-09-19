<?php

namespace App\Queries\ServiceCenters;

use App\Enums\ServiceCenterStatus;
use App\Models\ServiceCenter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class AdminServiceCenterQuery
{
    /** @return LengthAwarePaginator<int, ServiceCenter> */
    public function paginate(
        ?string $search,
        ?ServiceCenterStatus $status,
        ?bool $verified,
    ): LengthAwarePaginator {
        return ServiceCenter::query()
            ->with(['owner:id,name,email', 'city.governorate'])
            ->when($search !== null, function (Builder $query) use ($search): Builder {
                $escapedSearch = addcslashes($search, '%_\\');

                return $query->where(function (Builder $searchQuery) use ($escapedSearch): void {
                    $searchQuery
                        ->where('name', 'like', "%{$escapedSearch}%")
                        ->orWhere('phone', 'like', "%{$escapedSearch}%")
                        ->orWhereHas('owner', fn (Builder $ownerQuery): Builder => $ownerQuery
                            ->where(fn (Builder $identityQuery): Builder => $identityQuery
                                ->where('name', 'like', "%{$escapedSearch}%")
                                ->orWhere('email', 'like', "%{$escapedSearch}%")));
                });
            })
            ->when($status !== null, fn (Builder $query): Builder => $query->where('status', $status))
            ->when(
                $verified !== null,
                fn (Builder $query): Builder => $verified
                    ? $query->whereNotNull('verified_at')
                    : $query->whereNull('verified_at'),
            )
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();
    }

    public function findOrFail(int $serviceCenterId): ServiceCenter
    {
        return ServiceCenter::query()
            ->with([
                'owner:id,name,email,role',
                'city.governorate',
                'services',
                'carBrands',
                'openingHours',
                'images' => fn (Builder|Relation $query): Builder|Relation => $query
                    ->orderBy('sort_order')
                    ->orderBy('id'),
            ])
            ->withCount('publishedReviews')
            ->withAvg('publishedReviews', 'rating')
            ->findOrFail($serviceCenterId);
    }
}
