<?php

namespace App\Data\Parking;

final readonly class FavoriteParkingData
{
    public function __construct(
        public int $userId,
        public string $parkingIdentifier,
    ) {
    }
}