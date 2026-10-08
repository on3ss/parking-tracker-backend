<?php

namespace App\Filament\Resources\StreetParkings\Pages;

use App\Filament\Resources\StreetParkings\StreetParkingResource;
use App\Models\Location;
use Clickbar\Magellan\Data\Geometries\Point;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateStreetParking extends CreateRecord
{
    protected static string $resource = StreetParkingResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): Model {
            $locationData = $data['location'] ?? [];

            unset($data['location']);

            if (
                filled($locationData['latitude'] ?? null)
                && filled($locationData['longitude'] ?? null)
            ) {
                $location = Location::create([
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

                $data['location_id'] = $location->id;
            }

            return static::getModel()::create($data);
        });
    }
}
