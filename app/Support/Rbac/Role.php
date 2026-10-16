<?php

namespace App\Support\Rbac;

enum Role: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Manager = 'manager';
    case Viewer = 'viewer';

    /** @return Permission[] */
    public function permissions(): array
    {
        return match ($this) {
            // Full control of the team.
            self::Owner => Permission::cases(),

            // Everything except removing members.
            self::Admin => self::without(Permission::MembershipRemove),

            // Operational: read everything + write operational records.
            self::Manager => [
                ...Permission::views(),
                Permission::FacilityCreate,
                Permission::FacilityUpdate,
                Permission::AreaCreate,
                Permission::AreaUpdate,
                Permission::StreetParkingCreate,
                Permission::StreetParkingUpdate,
                Permission::OccupancyReportCreate,
                Permission::OccupancyReportUpdate,
            ],

            // Read-only.
            self::Viewer => Permission::views(),
        };
    }

    /**
     * All permissions except the given ones, compared by enum identity
     * (not by string casting).
     *
     * @return Permission[]
     */
    private static function without(Permission ...$excluded): array
    {
        return array_values(array_filter(
            Permission::cases(),
            static fn (Permission $p): bool => ! in_array($p, $excluded, true),
        ));
    }

    /** @return string[] */
    public function permissionNames(): array
    {
        return array_map(
            static fn (Permission $p): string => $p->value,
            $this->permissions(),
        );
    }

    /** @return string[] */
    public static function values(): array
    {
        return array_map(
            static fn (self $r): string => $r->value,
            self::cases(),
        );
    }
}
