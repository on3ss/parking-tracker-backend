<?php

namespace App\Http\Controllers\Api\V1\Parking;

use App\Actions\Parking\FindNearbyParking;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Parking\NearbyParkingRequest;
use App\Http\Resources\ParkingResource;

final class NearbyParkingController extends Controller
{
    public function __invoke(
        NearbyParkingRequest $request,
        FindNearbyParking $findNearbyParking,
    ) {
        $results = $findNearbyParking->execute(
            $request->data(),
        );

        return ParkingResource::collection(
            collect($results->items()),
        );
    }
}
