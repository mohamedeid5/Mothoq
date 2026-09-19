<?php

namespace App\Queries\Catalog;

use App\Models\CarBrand;
use App\Models\City;
use App\Models\Governorate;
use App\Models\Service;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;

final class PublicCatalogQuery
{
    /**
     * @return Collection<int, Governorate>
     */
    public function governorates(): Collection
    {
        return Governorate::query()
            ->active()
            ->select(['id', 'name', 'slug'])
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, Governorate>
     */
    public function governoratesWithCities(): Collection
    {
        return Governorate::query()
            ->active()
            ->select(['id', 'name', 'slug'])
            ->with(['cities' => fn (Relation $query): Relation => $query
                ->active()
                ->select(['id', 'governorate_id', 'name', 'slug'])
                ->orderBy('name')
                ->orderBy('id')])
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, City>
     */
    public function citiesForGovernorate(string $governorateSlug): Collection
    {
        $governorate = Governorate::query()
            ->active()
            ->where('slug', $governorateSlug)
            ->firstOrFail(['id']);

        return $governorate->cities()
            ->active()
            ->select(['id', 'governorate_id', 'name', 'slug'])
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, Service>
     */
    public function services(): Collection
    {
        return Service::query()
            ->active()
            ->select(['id', 'name', 'slug', 'description', 'icon'])
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, CarBrand>
     */
    public function carBrands(): Collection
    {
        return CarBrand::query()
            ->active()
            ->select(['id', 'name', 'slug', 'logo_path'])
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }
}
