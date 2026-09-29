<?php

namespace App\Filament\Admin\Resources\StreetParkings\Pages;

use App\Actions\Parking\UpdateStreetParking;
use App\Filament\Admin\Resources\StreetParkings\StreetParkingResource;
use App\Support\Geo\GeoJson;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditStreetParking extends EditRecord
{
    protected static string $resource =
        StreetParkingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(
        array $data,
    ): array {
        $data['geometry'] =
            GeoJson::from(
                $this->record->geometry,
            );

        $location =
            $this->record->location;

        if ($location) {
            $coordinates =
                $location->coordinates;

            $data['location'] = [
                'address_line1' =>
                    $location->address_line1,

                'address_line2' =>
                    $location->address_line2,

                'locality' =>
                    $location->locality,

                'administrative_area' =>
                    $location->administrative_area,

                'postal_code' =>
                    $location->postal_code,

                'country_code' =>
                    $location->country_code,

                'latitude' =>
                    $coordinates?->getLatitude(),

                'longitude' =>
                    $coordinates?->getLongitude(),
            ];
        }

        return $data;
    }

    protected function handleRecordUpdate(
        Model $record,
        array $data,
    ): Model {
        return app(UpdateStreetParking::class)
            ->handle(
                $record,
                $data,
            );
    }
}