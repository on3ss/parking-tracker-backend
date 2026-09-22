<?php

namespace App\Http\Controllers\Api\V1\Parking;

use App\Actions\Parking\FavoriteParking;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Parking\FavoriteParkingRequest;
use App\Http\Resources\FavoriteResource;

final class FavoriteParkingController extends Controller
{
    public function __invoke(
        FavoriteParkingRequest $request,
        FavoriteParking $favoriteParking,
    ): FavoriteResource {
        return new FavoriteResource(
            $favoriteParking->execute(
                userId: $request->user()->id,
                parkingIdentifier: $request->parkingIdentifier(),
            ),
        );
    }
}