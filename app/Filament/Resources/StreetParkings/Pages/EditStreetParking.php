<?php

namespace App\Filament\Resources\StreetParkings\Pages;

use App\Filament\Resources\StreetParkings\StreetParkingResource;
use App\Filament\Support\StripGeometry;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditStreetParking extends EditRecord
{
    protected static string $resource = StreetParkingResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return StripGeometry::from($data);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
