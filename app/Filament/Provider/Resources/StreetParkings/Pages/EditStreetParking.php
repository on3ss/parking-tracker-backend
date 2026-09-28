<?php

namespace App\Filament\Provider\Resources\StreetParkings\Pages;

use App\Filament\Provider\Resources\StreetParkings\StreetParkingResource;
use Clickbar\Magellan\Data\Geometries\LineString;
use Clickbar\Magellan\Data\Geometries\Point;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditStreetParking extends EditRecord
{
    protected static string $resource = StreetParkingResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        unset($data['geometry']);

        $location = $this->record->location;

        if ($location) {
            $coordinates = $location->coordinates;

            $data['location'] = [
                'address_line1' => $location->address_line1,
                'address_line2' => $location->address_line2,
                'locality' => $location->locality,
                'administrative_area' => $location->administrative_area,
                'postal_code' => $location->postal_code,
                'country_code' => $location->country_code,
                'latitude' => $coordinates?->getLatitude(),
                'longitude' => $coordinates?->getLongitude(),
            ];
        }

        $geometry = $this->record->geometry;

        if ($geometry) {
            $points = $geometry->getPoints();

            $start = $points[0] ?? null;
            $end = $points[1] ?? null;

            $data['geometry_start_latitude'] = $start?->getLatitude();
            $data['geometry_start_longitude'] = $start?->getLongitude();
            $data['geometry_end_latitude'] = $end?->getLatitude();
            $data['geometry_end_longitude'] = $end?->getLongitude();
        }

        return $data;
    }

    protected function handleRecordUpdate(
        Model $record,
        array $data,
    ): Model {
        return DB::transaction(function () use ($record, $data): Model {
            $location = $data['location'] ?? [];

            unset($data['location'], $data['geometry']);

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

            $record->update($data);

            $hasCoordinates =
                filled($location['latitude'] ?? null) &&
                filled($location['longitude'] ?? null);

            if ($hasCoordinates) {
                $record->location()->updateOrCreate(
                    [],
                    [
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
                    ],
                );
            }

            return $record->refresh();
        });
    }
}
