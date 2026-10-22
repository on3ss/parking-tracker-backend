<?php

namespace App\Support\Parking;

use InvalidArgumentException;

final class ParkingIdentifierParser
{
    /**
     * @return array{type: 'facility'|'street', id: int}
     */
    public function parse(string $identifier): array
    {
        if (
            ! preg_match(
                '/^(facility|street):([1-9][0-9]*)$/',
                $identifier,
                $matches,
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid parking identifier.',
            );
        }

        return [
            'type' => $matches[1],
            'id' => (int) $matches[2],
        ];
    }
}
