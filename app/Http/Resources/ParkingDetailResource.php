<?php

namespace App\Http\Resources;

use App\Data\Parking\ParkingIdentifier;
use App\Models\ParkingFacility;
use App\Models\StreetParking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ParkingDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var ParkingFacility|StreetParking $parking */
        $parking = $this->resource;

        $type = match (true) {
            $parking instanceof ParkingFacility => 'facility',
            $parking instanceof StreetParking => 'street',
        };

        return [
            'id' => ParkingIdentifier::for($parking),
            'type' => $type,
            'name' => $parking->name,

            'provider' => $parking->provider ? [
                'id' => $parking->provider->id,
                'name' => $parking->provider->name,
                'type' => $parking->provider->type,
            ] : null,

            'location' => $parking->location ? [
                'address_line1' => $parking->location->address_line1,
                'locality' => $parking->location->locality,
                'administrative_area' => $parking->location->administrative_area,
                'postal_code' => $parking->location->postal_code,
                'country_code' => $parking->location->country_code,
                'coordinates' => $parking->location->coordinates,
            ] : null,

            'capacity' => $parking->capacity,

            'availability' => [
                'status' => $parking->availability_status,
                'available_spaces' => $parking->available_spaces,
                'updated_at' => $parking->availability_updated_at,
            ],
        ];
    }
}
