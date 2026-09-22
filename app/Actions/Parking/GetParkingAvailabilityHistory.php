<?php

namespace App\Actions\Parking;

use App\Data\Parking\GetParkingAvailabilityHistoryData;
use App\Models\OccupancyReport;
use App\Models\ParkingFacility;
use Illuminate\Pagination\LengthAwarePaginator;

final class GetParkingAvailabilityHistory
{
    public function __construct(
        private ResolveParkingIdentifier $resolveParkingIdentifier,
    ) {}

    public function execute(
        GetParkingAvailabilityHistoryData $data,
    ): LengthAwarePaginator {
        $parking = $this->resolveParkingIdentifier->execute(
            $data->parkingIdentifier,
        );

        $query = OccupancyReport::query()
            ->orderByDesc('reported_at')
            ->orderByDesc('id');

        if ($parking instanceof ParkingFacility) {
            $query->where(
                'parking_facility_id',
                $parking->id,
            );
        } else {
            $query->where(
                'street_parking_id',
                $parking->id,
            );
        }

        return $query->paginate(
            perPage: $data->perPage,
            page: $data->page,
        );
    }
}
