<?php

namespace App\Policies;

use App\Models\ParkingArea;
use App\Models\ParkingFacility;
use App\Models\User;
use App\Services\Rbac\PlatformAuthorization;
use App\Services\Rbac\ProviderAuthorization;
use App\Support\Rbac\Permission;

final class ParkingAreaPolicy
{
    public function __construct(
        private readonly ProviderAuthorization $providerAuthorization,
        private readonly PlatformAuthorization $platformAuthorization,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->platformAuthorization->isAdmin($user)
            || $this->providerAuthorization->allowsCurrent(
                $user,
                Permission::AreaView,
            );
    }

    public function view(User $user, ParkingArea $area): bool
    {
        return $this->platformAuthorization->isAdmin($user)
            || $this->providerAuthorization->allows(
                $user,
                $this->providerId($area),
                Permission::AreaView,
            );
    }

    public function create(User $user): bool
    {
        return $this->platformAuthorization->isAdmin($user)
            || $this->providerAuthorization->allowsCurrent(
                $user,
                Permission::AreaCreate,
            );
    }

    /**
     * Authorize creation from a facility relation manager.
     *
     * The provider is determined from the parent facility, not
     * from whichever tenant happens to be active.
     */
    public function createForFacility(
        User $user,
        ParkingFacility $facility,
    ): bool {
        return $this->platformAuthorization->isAdmin($user)
            || $this->providerAuthorization->allows(
                $user,
                $facility->parking_provider_id,
                Permission::AreaCreate,
            );
    }

    public function update(User $user, ParkingArea $area): bool
    {
        return $this->platformAuthorization->isAdmin($user)
            || $this->providerAuthorization->allows(
                $user,
                $this->providerId($area),
                Permission::AreaUpdate,
            );
    }

    public function delete(User $user, ParkingArea $area): bool
    {
        return $this->platformAuthorization->isAdmin($user)
            || $this->providerAuthorization->allows(
                $user,
                $this->providerId($area),
                Permission::AreaDelete,
            );
    }

    /**
     * Authorize facility-scoped bulk operations without an individual area.
     */
    public function deleteForFacility(
        User $user,
        ParkingFacility $facility,
    ): bool {
        return $this->platformAuthorization->isAdmin($user)
            || $this->providerAuthorization->allows(
                $user,
                $facility->parking_provider_id,
                Permission::AreaDelete,
            );
    }

    public function restore(User $user, ParkingArea $area): bool
    {
        return $this->delete($user, $area);
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
