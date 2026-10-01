<?php

namespace App\Actions\Parking;

use App\Models\ParkingFacility;
use App\Models\StreetParking;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class ResolveParkingIdentifier
{
    public function execute(
        string $identifier,
        bool $lockForUpdate = false,
    ): ParkingFacility|StreetParking {
        [$type, $id] = $this->parse($identifier);

        $query = match ($type) {
            'facility' => ParkingFacility::query(),
            'street' => StreetParking::query(),
        };

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->findOrFail($id);
    }

    /**
     * @return array{0: 'facility'|'street', 1: int}
     */
    private function parse(string $identifier): array
    {
        if (
            ! preg_match(
                '/^(facility|street):([1-9][0-9]*)$/',
                $identifier,
                $matches,
            )
        ) {
            throw (new ModelNotFoundException)->setModel(
                ParkingFacility::class,
            );
        }

        return [
            $matches[1],
            (int) $matches[2],
        ];
    }
}
