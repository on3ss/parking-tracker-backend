<?php

namespace App\Http\Controllers\Api\V1\Parking;

use App\Actions\Parking\ReportParkingAvailability;
use App\Data\Parking\ReportParkingAvailabilityData;
use App\Enums\ParkingSource;
use App\Http\Controllers\Controller;
use App\Http\Requests\Parking\ReportParkingAvailabilityRequest;
use App\Http\Resources\ParkingDetailResource;

final class ReportParkingAvailabilityController extends Controller
{
    public function __invoke(
        ReportParkingAvailabilityRequest $request,
        string $parking,
        ReportParkingAvailability $reportParkingAvailability,
    ): ParkingDetailResource {
        $parkingModel = $reportParkingAvailability->execute(
            new ReportParkingAvailabilityData(
                parkingIdentifier: $parking,
                availableSpaces: $request->availableSpaces(),
                occupiedSpaces: $request->occupiedSpaces(),
                confidence: $request->confidence(),
            ),
            source: ParkingSource::USER,
            userId: $request->user()->id,
        );

        $parkingModel->load([
            'location',
            'provider',
        ]);

        return new ParkingDetailResource($parkingModel);
    }
}