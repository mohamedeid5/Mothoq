<?php

namespace App\Actions\ServiceCenters;

use App\Data\ServiceCenters\CreateServiceCenterData;
use App\Enums\ServiceCenterStatus;
use App\Enums\UserRole;
use App\Models\ServiceCenter;
use App\Models\User;
use App\Support\UniqueSlugGenerator;
use Illuminate\Support\Facades\DB;

final class CreateServiceCenterAction
{
    public function __construct(private UniqueSlugGenerator $slugGenerator) {}

    public function handle(CreateServiceCenterData $data): ServiceCenter
    {
        return DB::transaction(function () use ($data): ServiceCenter {
            $owner = User::query()
                ->whereIn('role', [UserRole::Customer->value, UserRole::CenterOwner->value])
                ->lockForUpdate()
                ->findOrFail($data->ownerId);

            if ($owner->role === UserRole::Customer) {
                $owner->update(['role' => UserRole::CenterOwner]);
            }

            return ServiceCenter::query()->create([
                ...$data->toArray(),
                'owner_id' => $owner->id,
                'slug' => $this->slugGenerator->generate(
                    source: $data->name,
                    modelClass: ServiceCenter::class,
                    column: 'slug',
                    fallback: 'service-center',
                ),
                'status' => ServiceCenterStatus::Draft,
                'verified_at' => null,
            ]);
        });
    }
}
