<?php

namespace App\Actions\Parking;

use App\Models\ParkingFacility;
use App\Models\StreetParking;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

final class ResolveParkingIdentifier
{
    public function execute(string $identifier): Model
    {
        [$type, $id] = $this->parse($identifier);

        return match ($type) {
            'facility' => ParkingFacility::query()->findOrFail($id),
            'street' => StreetParking::query()->findOrFail($id),
        };
    }

    /**
     * @return array{0: string, 1: int}
     */
    private function parse(string $identifier): array
    {
        if (!preg_match('/^(facility|street):([1-9][0-9]*)$/', $identifier, $matches)) {
            throw new InvalidArgumentException(
                "Invalid parking identifier: {$identifier}",
            );
        }

        return [
            $matches[1],
            (int) $matches[2],
        ];
    }
}