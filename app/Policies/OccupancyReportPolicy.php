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
            return $report->parkingFacility()
                ->withTrashed()
                ->value('parking_provider_id');
        }

        if ($report->street_parking_id !== null) {
            return $report->streetParking()
                ->withTrashed()
                ->value('parking_provider_id');
        }

        return null;
    }
}
