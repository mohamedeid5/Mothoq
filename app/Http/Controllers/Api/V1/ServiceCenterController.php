<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ServiceCenterStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexServiceCenterRequest;
use App\Http\Resources\Api\V1\ServiceCenterResource;
use App\Http\Resources\Api\V1\ServiceCenterSummaryResource;
use App\Models\ServiceCenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServiceCenterController extends Controller
{
    public function index(IndexServiceCenterRequest $request): AnonymousResourceCollection
    {
        $serviceCenters = ServiceCenter::query()
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
            ->published()
            ->publiclyAvailable()
            ->with([
                'city:id,governorate_id,name,slug',
                'city.governorate:id,name,slug',
                'services' => fn (Builder|Relation $query): Builder|Relation => $query
                    ->where('is_active', true)
                    ->orderBy('name'),
                'carBrands' => fn (Builder|Relation $query): Builder|Relation => $query
                    ->where('is_active', true)
                    ->orderBy('name'),
                'coverImage:id,service_center_id,path,alt_text,is_cover,sort_order',
            ])
            ->withCount('publishedReviews')
            ->withAvg('publishedReviews', 'rating')
            ->when($request->filled('governorate'), fn (Builder $query): Builder => $query
                ->inGovernorate($request->string('governorate')->toString()))
            ->when($request->filled('city'), fn (Builder $query): Builder => $query
                ->inCity($request->string('city')->toString()))
            ->when($request->filled('service'), fn (Builder $query): Builder => $query
                ->providesService($request->string('service')->toString()))
            ->when($request->filled('car_brand'), fn (Builder $query): Builder => $query
                ->supportsCarBrand($request->string('car_brand')->toString()))
            ->when(
                $request->has('verified'),
                fn (Builder $query): Builder => $request->boolean('verified')
                    ? $query->verified()
                    : $query->whereNull('verified_at'),
            )
            ->orderByDesc('verified_at')
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return ServiceCenterSummaryResource::collection($serviceCenters);
    }

    public function show(ServiceCenter $serviceCenter): ServiceCenterResource
    {
        abort_unless($serviceCenter->status === ServiceCenterStatus::Published, 404);

        $serviceCenter->load([
            'city:id,governorate_id,name,slug,is_active',
            'city.governorate:id,name,slug,is_active',
        ]);

        abort_unless($serviceCenter->city->is_active && $serviceCenter->city->governorate->is_active, 404);

        $serviceCenter->load([
            'services' => fn (Builder|Relation $query): Builder|Relation => $query
                ->where('is_active', true)
                ->orderBy('name'),
            'carBrands' => fn (Builder|Relation $query): Builder|Relation => $query
                ->where('is_active', true)
                ->orderBy('name'),
            'images' => fn (Builder|Relation $query): Builder|Relation => $query
                ->orderBy('sort_order')
                ->orderBy('id'),
            'coverImage:id,service_center_id,path,alt_text,is_cover,sort_order',
            'openingHours',
            'publishedReviews' => fn (Builder|Relation $query): Builder|Relation => $query
                ->with('user:id,name')
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->limit(5),
        ])->loadCount('publishedReviews')->loadAvg('publishedReviews', 'rating');

        return new ServiceCenterResource($serviceCenter);
    }
}
