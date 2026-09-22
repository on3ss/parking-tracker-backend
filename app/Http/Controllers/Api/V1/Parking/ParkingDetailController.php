<?php

namespace App\Http\Controllers\Api\V1\Parking;

use App\Actions\Parking\ResolveParkingIdentifier;
use App\Http\Controllers\Controller;
use App\Http\Resources\ParkingDetailResource;

final class ParkingDetailController extends Controller
{
    public function __invoke(
        string $parking,
        ResolveParkingIdentifier $resolveParkingIdentifier,
    ): ParkingDetailResource {
        $model = $resolveParkingIdentifier->execute($parking);

        $model->load([
            'location',
            'provider',
        ]);

        return new ParkingDetailResource($model);
    }
}
