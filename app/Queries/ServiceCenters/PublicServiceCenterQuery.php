<?php

namespace App\Queries\ServiceCenters;

use App\Models\ServiceCenter;
use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

final class PublicServiceCenterQuery
{
    /**
     * @return LengthAwarePaginator<int, ServiceCenter>
     */
    public function paginate(PublicServiceCenterFilters $filters): LengthAwarePaginator
    {
        return $this->baseQuery()
            ->select([
                'id',
                'city_id',
                'name',
                'slug',
                'description',
                'phone',
                'address',
                'latitude',
                'longitude',
                'verified_at',
            ])
            ->with($this->summaryRelations())
            ->withCount('publishedReviews')
            ->withAvg('publishedReviews', 'rating')
            ->when($filters->governorate !== null, fn (Builder $query): Builder => $query
                ->inGovernorate($filters->governorate))
            ->when($filters->city !== null, fn (Builder $query): Builder => $query
                ->inCity($filters->city))
            ->when($filters->service !== null, fn (Builder $query): Builder => $query
                ->providesService($filters->service))
            ->when($filters->carBrand !== null, fn (Builder $query): Builder => $query
                ->supportsCarBrand($filters->carBrand))
            ->when(
                $filters->verified !== null,
                fn (Builder $query): Builder => $filters->verified
                    ? $query->verified()
                    : $query->whereNull('verified_at'),
            )
            ->orderByDesc('verified_at')
            ->orderByDesc('id')
            ->paginate(
                perPage: $filters->perPage,
                page: $filters->page,
            );
    }

    public function findBySlugOrFail(string $slug): ServiceCenter
    {
        return $this->baseQuery()
            ->where('slug', $slug)
            ->with([
                ...$this->summaryRelations(),
                'images' => fn (Builder|Relation $query): Builder|Relation => $query
                    ->orderBy('sort_order')
                    ->orderBy('id'),
                'openingHours',
                'publishedReviews' => fn (Builder|Relation $query): Builder|Relation => $query
                    ->with('user:id,name')
                    ->orderByDesc('published_at')
                    ->orderByDesc('id')
                    ->limit(5),
            ])
            ->withCount('publishedReviews')
            ->withAvg('publishedReviews', 'rating')
            ->firstOrFail();
    }

    private function baseQuery(): Builder
    {
        return ServiceCenter::query()
            ->published()
            ->publiclyAvailable();
    }

    /**
     * @return array<string, Closure|string>
     */
    private function summaryRelations(): array
    {
        return [
            'city:id,governorate_id,name,slug',
            'city.governorate:id,name,slug',
            'services' => fn (Builder|Relation $query): Builder|Relation => $query
                ->where('is_active', true)
                ->orderBy('name'),
            'carBrands' => fn (Builder|Relation $query): Builder|Relation => $query
                ->where('is_active', true)
                ->orderBy('name'),
            'coverImage:id,service_center_id,path,alt_text,is_cover,sort_order',
        ];
    }
}
