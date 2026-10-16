<?php

namespace App\Policies;

use App\Models\ParkingProvider;
use App\Models\User;
use App\Services\Rbac\ProviderAuthorization;
use App\Support\Rbac\Permission;

final class ParkingProviderPolicy
{
    public function __construct(
        private readonly ProviderAuthorization $authorization,
    ) {
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(
        User $user,
        ParkingProvider $provider,
    ): bool {
        return $this->authorization->allows(
            $user,
            $provider->getKey(),
            Permission::ProviderView,
        );
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(
        User $user,
        ParkingProvider $provider,
    ): bool {
        return $this->authorization->allows(
            $user,
            $provider->getKey(),
            Permission::ProviderUpdate,
        );
    }

    public function delete(
        User $user,
        ParkingProvider $provider,
    ): bool {
        return false;
    }
}