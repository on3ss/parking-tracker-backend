<?php

namespace App\Policies;

use App\Models\ParkingFacility;
use App\Models\User;
use App\Services\Rbac\PlatformAuthorization;
use App\Services\Rbac\ProviderAuthorization;
use App\Support\Rbac\Permission;

final class ParkingFacilityPolicy
{
    public function __construct(
        private readonly ProviderAuthorization $providerAuthorization,
        private readonly PlatformAuthorization $platformAuthorization,
    ) {}

    public function viewAny(User $user): bool
    {
        if ($this->platformAuthorization->isAdmin($user)) {
            return true;
        }

        return $this->providerAuthorization->allowsCurrent(
            $user,
            Permission::FacilityView,
        );
    }

    public function view(User $user, ParkingFacility $facility): bool
    {
        if ($this->platformAuthorization->isAdmin($user)) {
            return true;
        }

        return $this->providerAuthorization->allows(
            $user,
            $facility->parking_provider_id,
            Permission::FacilityView,
        );
    }

    public function create(User $user): bool
    {
        if ($this->platformAuthorization->isAdmin($user)) {
            return true;
        }

        return $this->providerAuthorization->allowsCurrent(
            $user,
            Permission::FacilityCreate,
        );
    }

    public function update(User $user, ParkingFacility $facility): bool
    {
        if ($this->platformAuthorization->isAdmin($user)) {
            return true;
        }

        return $this->providerAuthorization->allows(
            $user,
            $facility->parking_provider_id,
            Permission::FacilityUpdate,
        );
    }

    public function delete(User $user, ParkingFacility $facility): bool
    {
        if ($this->platformAuthorization->isAdmin($user)) {
            return true;
        }

        return $this->providerAuthorization->allows(
            $user,
            $facility->parking_provider_id,
            Permission::FacilityDelete,
        );
    }

    public function restore(User $user, ParkingFacility $facility): bool
    {
        return $this->delete($user, $facility);
    }

    public function forceDelete(User $user, ParkingFacility $facility): bool
    {
        return $this->delete($user, $facility);
    }
}
