<?php

namespace App\Actions\Parking;

use App\Data\Parking\FavoriteParkingData;
use App\Models\Favorite;
use App\Models\ParkingFacility;
use App\Models\StreetParking;

final class FavoriteParking
{
    public function __construct(
        private ResolveParkingIdentifier $resolveParkingIdentifier,
    ) {
    }

    public function execute(
        FavoriteParkingData $data,
    ): Favorite {
        $parking = $this->resolveParkingIdentifier->execute(
            $data->parkingIdentifier,
        );

        return Favorite::query()->firstOrCreate([
            'user_id' => $data->userId,
            'favorable_type' => match (true) {
                $parking instanceof ParkingFacility => 'facility',
                $parking instanceof StreetParking => 'street',
            },
            'favorable_id' => $parking->id,
        ]);
    }
}