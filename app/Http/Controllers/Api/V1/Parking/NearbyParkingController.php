<?php

namespace App\Http\Controllers\Api\V1\Parking;

use App\Actions\Parking\SearchParking;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Parking\NearbyParkingRequest;
use App\Http\Resources\ParkingResource;

final class NearbyParkingController extends Controller
{
    public function __invoke(
        NearbyParkingRequest $request,
        SearchParking $searchParking,
    ) {
        $results = $searchParking->execute($request->data());

        return ParkingResource::collection(
            collect($results->items()),
        )->additional([
                    'meta' => [
                        'current_page' => $results->currentPage(),
                        'per_page' => $results->perPage(),
                        'total' => $results->total(),
                        'last_page' => $results->lastPage(),
                    ],
                ]);
    }
}
