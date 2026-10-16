<?php

namespace App\Policies;

use App\Models\ParkingFacility;
use App\Models\User;
use App\Services\Rbac\ProviderAuthorization;
use App\Support\Rbac\Permission;

final class ParkingFacilityPolicy
{
    public function __construct(
        private readonly ProviderAuthorization $authorization,
    ) {
    }

    public function viewAny(User $user): bool
    {
        return $this->authorization->allowsCurrent(
            $user,
            Permission::FacilityView,
        );
    }

    public function view(User $user, ParkingFacility $facility): bool
    {
        return $this->authorization->allows(
            $user,
            $facility->parking_provider_id,
            Permission::FacilityView,
        );
    }

    public function create(User $user): bool
    {
        return $this->authorization->allowsCurrent(
            $user,
            Permission::FacilityCreate,
        );
    }

    public function update(User $user, ParkingFacility $facility): bool
    {
        return $this->authorization->allows(
            $user,
            $facility->parking_provider_id,
            Permission::FacilityUpdate,
        );
    }

    public function delete(User $user, ParkingFacility $facility): bool
    {
        return $this->authorization->allows(
            $user,
            $facility->parking_provider_id,
            Permission::FacilityDelete,
        );
    }

    public function restore(User $user, ParkingFacility $facility): bool
    {
        return $this->authorization->allows(
            $user,
            $facility->parking_provider_id,
            Permission::FacilityDelete,
        );
    }

    public function forceDelete(User $user, ParkingFacility $facility): bool
    {
        return $this->delete($user, $facility);
    }
}