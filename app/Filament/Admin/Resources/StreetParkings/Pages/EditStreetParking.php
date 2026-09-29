<?php

namespace App\Filament\Admin\Resources\StreetParkings\Pages;

use App\Filament\Admin\Resources\StreetParkings\StreetParkingResource;
use App\Filament\Forms\Components\GeometryPicker;
use App\Models\Location;
use Clickbar\Magellan\Data\Geometries\Point;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditStreetParking extends EditRecord
{
    protected static string $resource = StreetParkingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Hand the picker GeoJSON explicitly, rather than relying on how
        // attributesToArray() happens to serialize the Magellan cast.
        $data['geometry'] = GeometryPicker::toGeoJson($this->record->geometry);

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

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data): Model {
            $locationData = $data['location'] ?? [];

            unset($data['location']);

            // $data['geometry'] is already a Magellan LineString (SRID 4326),
            // produced by GeometryPicker's dehydrateStateUsing().

            if (
                filled($locationData['latitude'] ?? null) &&
                filled($locationData['longitude'] ?? null)
            ) {
                $location = $record->location ?? new Location();

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
                ])->save();

                $data['location_id'] = $location->id;
            }

            $record->update($data);

            return $record->refresh();
        });
    }
}
