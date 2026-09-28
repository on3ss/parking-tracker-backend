<?php

namespace App\Filament\Admin\Resources\StreetParkings\Pages;

use App\Filament\Admin\Resources\StreetParkings\StreetParkingResource;
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

    if (! $location) {
        return $data;
    }

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

    return $data;
}

    protected function handleRecordUpdate(
    Model $record,
    array $data,
): Model {
    return DB::transaction(function () use ($record, $data): Model {
        $location = $data['location'] ?? [];

        unset($data['location']);

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