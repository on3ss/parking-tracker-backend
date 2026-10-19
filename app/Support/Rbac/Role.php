<?php

namespace App\Support\Rbac;

enum Role: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Manager = 'manager';
    case Viewer = 'viewer';

    /**
     * Explicit provider-role permissions.
     *
     * New permissions must be deliberately assigned to each role.
     *
     * @return Permission[]
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Owner => [
                Permission::FacilityView,
                Permission::FacilityCreate,
                Permission::FacilityUpdate,
                Permission::FacilityDelete,

                Permission::AreaView,
                Permission::AreaCreate,
                Permission::AreaUpdate,
                Permission::AreaDelete,

                Permission::StreetParkingView,
                Permission::StreetParkingCreate,
                Permission::StreetParkingUpdate,
                Permission::StreetParkingDelete,

                Permission::OccupancyReportView,
                Permission::OccupancyReportCreate,
                Permission::OccupancyReportDelete,

                Permission::ProviderView,
                Permission::ProviderUpdate,

                Permission::MembershipView,
                Permission::MembershipInvite,
                Permission::MembershipUpdate,
                Permission::MembershipRemove,
            ],

            self::Admin => [
                Permission::FacilityView,
                Permission::FacilityCreate,
                Permission::FacilityUpdate,
                Permission::FacilityDelete,

                Permission::AreaView,
                Permission::AreaCreate,
                Permission::AreaUpdate,
                Permission::AreaDelete,

                Permission::StreetParkingView,
                Permission::StreetParkingCreate,
                Permission::StreetParkingUpdate,
                Permission::StreetParkingDelete,

                Permission::OccupancyReportView,
                Permission::OccupancyReportCreate,
                Permission::OccupancyReportDelete,

                Permission::ProviderView,
                Permission::ProviderUpdate,

                Permission::MembershipView,
                Permission::MembershipInvite,
                Permission::MembershipUpdate,
            ],

            self::Manager => [
                Permission::FacilityView,
                Permission::FacilityCreate,
                Permission::FacilityUpdate,

                Permission::AreaView,
                Permission::AreaCreate,
                Permission::AreaUpdate,

                Permission::StreetParkingView,
                Permission::StreetParkingCreate,
                Permission::StreetParkingUpdate,

                Permission::OccupancyReportView,
                Permission::OccupancyReportCreate,

                Permission::ProviderView,
                Permission::MembershipView,
            ],

            self::Viewer => [
                Permission::FacilityView,
                Permission::AreaView,
                Permission::StreetParkingView,
                Permission::OccupancyReportView,
                Permission::ProviderView,
                Permission::MembershipView,
            ],
        };
    }

    /** @return string[] */
    public function permissionNames(): array
    {
        return array_map(
            static fn(Permission $permission): string => $permission->value,
            $this->permissions(),
        );
    }

    /** @return string[] */
    public static function values(): array
    {
        return array_map(
            static fn(self $role): string => $role->value,
            self::cases(),
        );
    }
}
