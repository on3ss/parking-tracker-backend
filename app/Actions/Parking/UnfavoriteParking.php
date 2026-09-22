<?php

namespace App\Actions\Parking;

use App\Data\Parking\FavoriteParkingData;
use App\Models\Favorite;
use App\Models\ParkingFacility;
use App\Models\StreetParking;

final class UnfavoriteParking
{
    public function __construct(
        private ResolveParkingIdentifier $resolveParkingIdentifier,
    ) {
    }

    public function execute(
        FavoriteParkingData $data,
    ): bool {
        $parking = $this->resolveParkingIdentifier->execute(
            $data->parkingIdentifier,
        );

        $morphType = match (true) {
            $parking instanceof ParkingFacility => 'facility',
            $parking instanceof StreetParking => 'street',
        };

        return Favorite::query()
            ->where('user_id', $data->userId)
            ->where('favorable_type', $morphType)
            ->where('favorable_id', $parking->id)
            ->delete() > 0;
    }
}