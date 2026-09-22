<?php

namespace App\Http\Controllers\Api\V1\Parking;

use App\Actions\Parking\UnfavoriteParking;
use App\Data\Parking\FavoriteParkingData;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class UnfavoriteParkingController extends Controller
{
    public function __invoke(
        Request $request,
        string $parking,
        UnfavoriteParking $unfavoriteParking,
    ): Response {
        $unfavoriteParking->execute(
            new FavoriteParkingData(
                userId: $request->user()->id,
                parkingIdentifier: $parking,
            ),
        );

        return response()->noContent();
    }
}