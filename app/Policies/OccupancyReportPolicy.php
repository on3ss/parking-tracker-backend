<?php

namespace App\Policies;

use App\Models\OccupancyReport;
use App\Models\User;
use App\Services\Rbac\PlatformAuthorization;
use App\Services\Rbac\ProviderAuthorization;
use App\Support\Rbac\Permission;

final class OccupancyReportPolicy
{
    public function __construct(
        private readonly ProviderAuthorization $providerAuthorization,
        private readonly PlatformAuthorization $platformAuthorization,
    ) {
    }

    public function viewAny(User $user): bool
    {
        return $this->platformAuthorization->isAdmin($user)
            || $this->providerAuthorization->allowsCurrent(
                $user,
                Permission::OccupancyReportView,
            );
    }

    public function view(User $user, OccupancyReport $report): bool
    {
        return $this->platformAuthorization->isAdmin($user)
            || $this->providerAuthorization->allows(
                $user,
                $this->providerId($report),
                Permission::OccupancyReportView,
            );
    }

    public function create(User $user): bool
    {
        return $this->platformAuthorization->isAdmin($user)
            || $this->providerAuthorization->allowsCurrent(
                $user,
                Permission::OccupancyReportCreate,
            );
    }

    /**
     * Occupancy reports are immutable.
     */
    public function update(User $user, OccupancyReport $report): bool
    {
        return false;
    }

    public function delete(User $user, OccupancyReport $report): bool
    {
        return $this->platformAuthorization->isAdmin($user)
            || $this->providerAuthorization->allows(
                $user,
                $this->providerId($report),
                Permission::OccupancyReportDelete,
            );
    }


    private function providerId(
        OccupancyReport $report,
    ): int|string|null {
        if ($report->parking_facility_id !== null) {
            if ($report->relationLoaded('parkingFacility')) {
                $facility = $report->getRelation('parkingFacility');

                if ($facility !== null) {
                    return $facility->parking_provider_id;
                }
            }

            return $report->parkingFacility()
                ->withTrashed()
                ->value('parking_provider_id');
        }

        if ($report->street_parking_id !== null) {
            if ($report->relationLoaded('streetParking')) {
                $streetParking = $report->getRelation('streetParking');

                if ($streetParking !== null) {
                    return $streetParking->parking_provider_id;
                }
            }

            return $report->streetParking()
                ->withTrashed()
                ->value('parking_provider_id');
        }

        return null;
    }

}
