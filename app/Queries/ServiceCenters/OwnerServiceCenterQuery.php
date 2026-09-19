<?php

namespace App\Queries\ServiceCenters;

use App\Models\ServiceCenter;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class OwnerServiceCenterQuery
{
    /**
     * @return LengthAwarePaginator<int, ServiceCenter>
     */
    public function paginateFor(User $owner): LengthAwarePaginator
    {
        return $owner->serviceCenters()
            ->with(['owner', 'city.governorate'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15);
    }

    public function findForOwnerOrFail(User $owner, int $serviceCenterId): ServiceCenter
    {
        return $owner->serviceCenters()
            ->with(['owner', 'city.governorate', 'services', 'carBrands', 'openingHours'])
            ->findOrFail($serviceCenterId);
    }
}
