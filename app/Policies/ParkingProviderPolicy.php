<?php

namespace App\Policies;

use App\Models\ParkingProvider;
use App\Models\User;
use App\Services\Rbac\PlatformAuthorization;
use App\Services\Rbac\ProviderAuthorization;
use App\Support\Rbac\Permission;

final class ParkingProviderPolicy
{
    public function __construct(
        private readonly ProviderAuthorization $providerAuthorization,
        private readonly PlatformAuthorization $platformAuthorization,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->platformAuthorization->isAdmin($user);
    }

    public function view(
        User $user,
        ParkingProvider $provider,
    ): bool {
        if ($this->platformAuthorization->isAdmin($user)) {
            return true;
        }

        return $this->providerAuthorization->allows(
            $user,
            $provider->getKey(),
            Permission::ProviderView,
        );
    }

    public function create(User $user): bool
    {
        // Provider creation must go through the registration workflow.
        return false;
    }

    public function update(
        User $user,
        ParkingProvider $provider,
    ): bool {
        if ($this->platformAuthorization->isAdmin($user)) {
            return true;
        }

        return $this->providerAuthorization->allows(
            $user,
            $provider->getKey(),
            Permission::ProviderUpdate,
        );
    }

    public function delete(
        User $user,
        ParkingProvider $provider,
    ): bool {
        // Keep deletion disabled until a dedicated workflow is defined.
        return false;
    }
}
