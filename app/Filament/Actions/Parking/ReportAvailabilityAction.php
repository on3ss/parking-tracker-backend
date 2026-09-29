<?php

namespace App\Filament\Actions\Parking;

use App\Actions\Parking\ReportParkingAvailability;
use App\Data\Parking\ReportParkingAvailabilityData;
use App\Enums\ParkingSource;
use App\Models\ParkingFacility;
use App\Models\StreetParking;
use App\Support\Parking\ParkingIdentifier;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Model;

final class ReportAvailabilityAction
{
    public static function make(): Action
    {
        return Action::make('reportAvailability')
            ->label(__('Report availability'))
            ->icon('heroicon-o-chart-bar')
            ->color('primary')
            ->modalHeading(__('Report parking availability'))
            ->modalDescription(
                __('Enter the number of spaces currently available.')
            )
            ->schema([
                TextInput::make('available_spaces')
                    ->label(__('Available spaces'))
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->maxValue(
                        fn(ParkingFacility|StreetParking $record): int =>
                            $record->capacity,
                    )
                    ->required(),
            ])
            ->fillForm(
                fn(ParkingFacility|StreetParking $record): array => [
                    'available_spaces' => $record->available_spaces,
                ],
            )
            ->action(function (Model $record, array $data, ): void {
                /** @var ParkingFacility|StreetParking $record */
                app(ReportParkingAvailability::class)->execute(
                    new ReportParkingAvailabilityData(
                        parkingIdentifier: ParkingIdentifier::for($record),
                        availableSpaces: (int) $data['available_spaces'],
                    ),
                    source: ParkingSource::OPERATOR,
                    userId: auth()->id(),
                );
            })
            ->successNotificationTitle(
                __('Availability reported successfully.'),
            );
    }
}