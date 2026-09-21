<?php

namespace App\Http\Controllers\Api\V1\Parking;

use App\Actions\Parking\SearchParking;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Parking\SearchParkingRequest;
use App\Http\Resources\ParkingResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ParkingIndexController extends Controller
{
    public function __invoke(
        SearchParkingRequest $request,
        SearchParking $searchParking,
    ): AnonymousResourceCollection {
        $results = $searchParking->execute(
            $request->data(),
        );

        return ParkingResource::collection($results);
    }
}