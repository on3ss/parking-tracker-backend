<?php

namespace App\Support\Rbac;

enum Permission: string
{
    // Facility
    case FacilityView = 'facility.view';
    case FacilityCreate = 'facility.create';
    case FacilityUpdate = 'facility.update';
    case FacilityDelete = 'facility.delete';

    // Parking area
    case AreaView = 'area.view';
    case AreaCreate = 'area.create';
    case AreaUpdate = 'area.update';
    case AreaDelete = 'area.delete';

    // Street parking
    case StreetParkingView = 'street_parking.view';
    case StreetParkingCreate = 'street_parking.create';
    case StreetParkingUpdate = 'street_parking.update';
    case StreetParkingDelete = 'street_parking.delete';

    // Occupancy report
    case OccupancyReportView = 'occupancy_report.view';
    case OccupancyReportCreate = 'occupancy_report.create';
    case OccupancyReportUpdate = 'occupancy_report.update';
    case OccupancyReportDelete = 'occupancy_report.delete';

    // Provider
    case ProviderView = 'provider.view';
    case ProviderUpdate = 'provider.update';

    // Membership
    case MembershipView = 'membership.view';
    case MembershipInvite = 'membership.invite';
    case MembershipUpdate = 'membership.update';
    case MembershipRemove = 'membership.remove';

    /** Every `<resource>.view` permission. */
    public static function views(): array
    {
        return array_values(array_filter(
            self::cases(),
            static fn(self $p) => str_ends_with($p->value, '.view'),
        ));
    }

    /** Every permission belonging to a resource, e.g. `Permission::for('facility')`. */
    public static function for(string $resource): array
    {
        $prefix = $resource . '.';

        return array_values(array_filter(
            self::cases(),
            static fn(self $p) => str_starts_with($p->value, $prefix),
        ));
    }

    /** @return string[] */
    public static function values(): array
    {
        return array_map(static fn(self $p) => $p->value, self::cases());
    }
}