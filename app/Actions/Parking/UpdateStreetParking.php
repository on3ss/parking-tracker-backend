<?php

namespace App\Actions\Parking;

use App\Models\Location;
use App\Models\StreetParking;
use Clickbar\Magellan\Data\Geometries\Point;
use Illuminate\Support\Facades\DB;

final class UpdateStreetParking
{
    public function handle(
        StreetParking $streetParking,
        array $data,
    ): StreetParking {
        return DB::transaction(
            function () use ($streetParking, $data): StreetParking {
                $locationData =
                    $data['location'] ?? [];

                unset($data['location']);

                if (
                    filled(
                        $locationData['latitude'] ?? null,
                    )
                    &&
                    filled(
                        $locationData['longitude'] ?? null,
                    )
                ) {
                    $location =
                        $streetParking->location
                        ?? new Location;

                    $location->fill([
                        'address_line1' => $locationData['address_line1'] ?? null,

                        'address_line2' => $locationData['address_line2'] ?? null,

                        'locality' => $locationData['locality'] ?? null,

                        'administrative_area' => $locationData['administrative_area'] ?? null,

                        'postal_code' => $locationData['postal_code'] ?? null,

                        'country_code' => $locationData['country_code'] ?? 'IN',

                        'coordinates' => Point::makeGeodetic(
                            latitude: (float) $locationData['latitude'],
                            longitude: (float) $locationData['longitude'],
                        ),
                    ]);

                    $location->save();

                    $data['location_id'] =
                        $location->id;
                }

                $streetParking->update($data);

                return $streetParking->refresh();
            },
        );
    }
}
