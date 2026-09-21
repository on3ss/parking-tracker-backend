<?php

namespace App\Http\Controllers\Api\V1\Parking;

use App\Actions\Parking\FindNearbyParking;
use App\Data\Parking\FindNearbyParkingData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Parking\NearbyParkingRequest;
use App\Http\Resources\ParkingResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class NearbyParkingController extends Controller
{
    public function __invoke(
        NearbyParkingRequest $request,
        FindNearbyParking $findNearbyParking,
    ): AnonymousResourceCollection {
        $results = $findNearbyParking->execute(
            new FindNearbyParkingData(
                latitude: $request->latitude(),
                longitude: $request->longitude(),
                radiusMeters: $request->radius(),
                limit: $request->limit(),
            ),
        );

        return ParkingResource::collection($results);
    }
}