<?php

namespace App\Http\Controllers\Api\V1\Parking;

use App\Actions\Parking\GetParkingAvailabilityHistory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Parking\AvailabilityHistoryRequest;
use App\Http\Resources\OccupancyReportResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ParkingAvailabilityHistoryController extends Controller
{
    public function __invoke(
        AvailabilityHistoryRequest $request,
        string $parking,
        GetParkingAvailabilityHistory $getParkingAvailabilityHistory,
    ): AnonymousResourceCollection {
        return OccupancyReportResource::collection(
            $getParkingAvailabilityHistory->execute(
                $request->data($parking),
            ),
        );
    }
}
