<?php

namespace App\Http\Resources;

use App\Data\Parking\ParkingSearchResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ParkingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var ParkingSearchResult $result */
        $result = $this->resource;

        $parking = $result->parking;

        return [
            'id' => $result->publicId(),

            'type' => $result->type(),

            'name' => $parking->name,

            'distance' => $result->distanceMeters === null
                ? null
                : [
                    'meters' => round($result->distanceMeters, 1),
                    'kilometers' => round(
                        $result->distanceMeters / 1000,
                        2,
                    ),
                ],

            'provider' => $parking->provider
                ? [
                    'id' => $parking->provider->id,
                    'name' => $parking->provider->name,
                    'type' => $parking->provider->type,
                ]
                : null,

            'location' => $parking->location
                ? [
                    'address_line1' => $parking->location->address_line1,
                    'locality' => $parking->location->locality,
                    'administrative_area' => $parking->location->administrative_area,
                    'postal_code' => $parking->location->postal_code,
                    'country_code' => $parking->location->country_code,
                    'coordinates' => $parking->location->coordinates,
                ]
                : null,

            'capacity' => $parking->capacity,

            'availability' => [
                'status' => $parking->availability_status,
                'available_spaces' => $parking->available_spaces,
                'updated_at' => $parking->availability_updated_at,
            ],
        ];
    }
}
