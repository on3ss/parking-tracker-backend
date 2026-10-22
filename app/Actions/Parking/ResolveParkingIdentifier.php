<?php

namespace App\Actions\Parking;

use App\Models\ParkingFacility;
use App\Models\StreetParking;
use App\Support\Parking\ParkingIdentifierParser;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

final class ResolveParkingIdentifier
{
    public function __construct(
        private ParkingIdentifierParser $identifierParser,
    ) {}

    public function execute(
        string $identifier,
        bool $lockForUpdate = false,
    ): ParkingFacility|StreetParking {
        try {
            $parsed = $this->identifierParser->parse($identifier);
        } catch (InvalidArgumentException) {
            throw (new ModelNotFoundException)->setModel(
                ParkingFacility::class,
            );
        }

        $query = match ($parsed['type']) {
            'facility' => ParkingFacility::query(),
            'street' => StreetParking::query(),
        };

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->findOrFail($parsed['id']);
    }
}
