<?php

namespace App\Actions\Parking;

use App\Actions\Parking\ResolveParkingIdentifier;
use App\Support\Parking\ParkingIdentifier;
use App\Models\Favorite;

final class UnfavoriteParking
{
    public function __construct(
        private ResolveParkingIdentifier $resolveParkingIdentifier,
    ) {
    }

    public function execute(
        int $userId,
        string $parkingIdentifier,
    ): bool {
        $parking = $this->resolveParkingIdentifier->execute(
            $parkingIdentifier,
        );

        return Favorite::query()
            ->where('user_id', $userId)
            ->where('favorable_type', ParkingIdentifier::type($parking))
            ->where('favorable_id', $parking->id)
            ->delete() > 0;
    }
}