<?php

namespace App\Filament\Provider\Resources\ParkingFacilities\Pages;

use App\Filament\Provider\Resources\ParkingFacilities\ParkingFacilityResource;
use App\Models\Location;
use Clickbar\Magellan\Data\Geometries\Point;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateParkingFacility extends CreateRecord
{
    protected static string $resource = ParkingFacilityResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): Model {
            $location = $data['location'];

            unset($data['location']);

            $location = Location::create([
                'address_line1' => $location['address_line1'] ?? null,
                'address_line2' => $location['address_line2'] ?? null,
                'locality' => $location['locality'] ?? null,
                'administrative_area' => $location['administrative_area'] ?? null,
                'postal_code' => $location['postal_code'] ?? null,
                'country_code' => $location['country_code'] ?? 'IN',
                'coordinates' => Point::makeGeodetic(
                    latitude: (float) $location['latitude'],
                    longitude: (float) $location['longitude'],
                ),
            ]);

            $data['location_id'] = $location->id;

            return static::getModel()::create($data);
        });
    }
}
