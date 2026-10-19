<?php

namespace App\Policies;

use App\Filament\Resources\ParkingFacilities\ParkingFacilityResource;
use App\Models\OccupancyReport;
use App\Models\ParkingFacility;
use App\Models\User;
use App\Services\Rbac\ProviderAuthorization;
use App\Support\Rbac\Permission;

final class OccupancyReportPolicy
{
    public function __construct(
        private readonly ProviderAuthorization $authorization,
    ) {
    }

    public function viewAny(User $user): bool
    {
        return $this->isGlobal()
            ? $this->isGlobalAdmin($user)
            : $this->authorization->allowsCurrent($user, Permission::FacilityView);
    }

    public function view(User $user, ParkingFacility $facility): bool
    {
        return $this->isGlobal()
            ? $this->isGlobalAdmin($user)
            : $this->authorization->allows($user, $facility->parking_provider_id, Permission::FacilityView);
    }

    public function create(User $user): bool
    {
        return $this->isGlobal()
            ? $this->isGlobalAdmin($user)
            : $this->authorization->allowsCurrent($user, Permission::FacilityCreate);
    }

    public function update(User $user, OccupancyReport $report): bool
    {
        return false;
    }

    public function delete(User $user, OccupancyReport $report): bool
    {
        return $this->authorization->allows(
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

    private function isGlobal(): bool
    {
        return ParkingFacilityResource::isGlobalContext();
    }

    private function isGlobalAdmin(User $user): bool
    {
        return (bool) $user->is_admin; // ← whatever §3 grep finds
    }
}