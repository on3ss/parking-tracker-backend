<?php

namespace App\Http\Controllers\Api\V1\Parking;

use App\Actions\Parking\ResolveParkingIdentifier;
use App\Http\Controllers\Controller;
use App\Http\Resources\ParkingDetailResource;
use Illuminate\Http\Request;

final class ParkingDetailController extends Controller
{
    public function __invoke(
        Request $request,
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