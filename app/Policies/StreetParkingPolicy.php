<?php

namespace App\Policies;

use App\Models\StreetParking;
use App\Models\User;
use App\Services\Rbac\PlatformAuthorization;
use App\Services\Rbac\ProviderAuthorization;
use App\Support\Rbac\Permission;

final class StreetParkingPolicy
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
            Permission::StreetParkingView,
        );
    }

    public function view(User $user, StreetParking $streetParking): bool
    {
        if ($this->platformAuthorization->isAdmin($user)) {
            return true;
        }

        return $this->providerAuthorization->allows(
            $user,
            $streetParking->parking_provider_id,
            Permission::StreetParkingView,
        );
    }

    public function create(User $user): bool
    {
        if ($this->platformAuthorization->isAdmin($user)) {
            return true;
        }

        return $this->providerAuthorization->allowsCurrent(
            $user,
            Permission::StreetParkingCreate,
        );
    }

    public function update(User $user, StreetParking $streetParking): bool
    {
        if ($this->platformAuthorization->isAdmin($user)) {
            return true;
        }

        return $this->providerAuthorization->allows(
            $user,
            $streetParking->parking_provider_id,
            Permission::StreetParkingUpdate,
        );
    }

    public function delete(User $user, StreetParking $streetParking): bool
    {
        if ($this->platformAuthorization->isAdmin($user)) {
            return true;
        }

        return $this->providerAuthorization->allows(
            $user,
            $streetParking->parking_provider_id,
            Permission::StreetParkingDelete,
        );
    }

    public function restore(User $user, StreetParking $streetParking): bool
    {
        return $this->delete($user, $streetParking);
    }

    public function forceDelete(User $user, StreetParking $streetParking): bool
    {
        return $this->delete($user, $streetParking);
    }
}
