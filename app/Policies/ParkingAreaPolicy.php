<?php

namespace App\Policies;

use App\Models\ParkingArea;
use App\Models\User;
use App\Services\Rbac\ProviderAuthorization;
use App\Support\Rbac\Permission;

final class ParkingAreaPolicy
{
    public function __construct(
        private readonly ProviderAuthorization $authorization,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->authorization->allowsCurrent(
            $user,
            Permission::AreaView,
        );
    }

    public function view(User $user, ParkingArea $area): bool
    {
        return $this->authorization->allows(
            $user,
            $this->providerId($area),
            Permission::AreaView,
        );
    }

    public function create(User $user): bool
    {
        return $this->authorization->allowsCurrent(
            $user,
            Permission::AreaCreate,
        );
    }

    public function update(User $user, ParkingArea $area): bool
    {
        return $this->authorization->allows(
            $user,
            $this->providerId($area),
            Permission::AreaUpdate,
        );
    }

    public function delete(User $user, ParkingArea $area): bool
    {
        return $this->authorization->allows(
            $user,
            $this->providerId($area),
            Permission::AreaDelete,
        );
    }

    public function restore(User $user, ParkingArea $area): bool
    {
        return $this->authorization->allows(
            $user,
            $this->providerId($area),
            Permission::AreaDelete,
        );
    }

    public function forceDelete(User $user, ParkingArea $area): bool
    {
        return $this->delete($user, $area);
    }

    private function providerId(ParkingArea $area): int|string|null
    {
        if ($area->relationLoaded('parkingFacility')) {
            $facility = $area->getRelation('parkingFacility');

            if ($facility !== null) {
                return $facility->parking_provider_id;
            }
        }

        return $area->parkingFacility()
            ->withTrashed()
            ->value('parking_provider_id');
    }
}
