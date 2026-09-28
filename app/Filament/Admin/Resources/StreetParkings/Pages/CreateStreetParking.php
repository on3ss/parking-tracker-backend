<?php

namespace App\Filament\Admin\Resources\StreetParkings\Pages;

use App\Filament\Admin\Resources\StreetParkings\StreetParkingResource;
use App\Models\Location;
use Clickbar\Magellan\Data\Geometries\LineString;
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

            $data['geometry'] = LineString::make([
                Point::makeGeodetic(
                    latitude: (float) $data['geometry_start_latitude'],
                    longitude: (float) $data['geometry_start_longitude'],
                ),
                Point::makeGeodetic(
                    latitude: (float) $data['geometry_end_latitude'],
                    longitude: (float) $data['geometry_end_longitude'],
                ),
            ]);

            unset(
                $data['geometry_start_latitude'],
                $data['geometry_start_longitude'],
                $data['geometry_end_latitude'],
                $data['geometry_end_longitude'],
            );

            return static::getModel()::create($data);
        });
    }
}