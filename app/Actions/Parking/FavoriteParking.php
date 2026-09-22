<?php

namespace App\Actions\Parking;

use App\Models\Favorite;
use App\Support\Parking\ParkingIdentifier;

final class FavoriteParking
{
    public function __construct(
        private ResolveParkingIdentifier $resolveParkingIdentifier,
    ) {}

    public function execute(
        int $userId,
        string $parkingIdentifier,
    ): Favorite {
        $parking = $this->resolveParkingIdentifier->execute(
            $parkingIdentifier,
        );

        return Favorite::query()->firstOrCreate([
            'user_id' => $userId,
            'favorable_type' => ParkingIdentifier::type($parking),
            'favorable_id' => $parking->id,
        ]);
    }
}
