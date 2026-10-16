<?php

namespace App\Filament\Resources\ParkingFacilities\Pages;

use App\Filament\Resources\ParkingFacilities\ParkingFacilityResource;
use App\Models\Location;
use App\Models\ParkingProvider;
use Clickbar\Magellan\Data\Geometries\Point;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateParkingFacility extends CreateRecord
{
    protected static string $resource = ParkingFacilityResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): Model {
            $panel = Filament::getCurrentPanel();

            if ($panel?->getId() === 'provider') {
                $tenant = Filament::getTenant();

                abort_unless($tenant instanceof ParkingProvider, 403);

                $data['parking_provider_id'] = $tenant->getKey();
            }

            $locationData = $data['location'] ?? [];

            unset($data['location']);

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

            $data['location_id'] = $location->getKey();

            return static::getModel()::create($data);
        });
    }
}
