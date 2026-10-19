<?php

namespace App\Filament\Actions\Parking;

use App\Actions\Parking\ReportParkingAvailability;
use App\Data\Parking\ReportParkingAvailabilityData;
use App\Enums\ParkingSource;
use App\Models\ParkingFacility;
use App\Models\StreetParking;
use App\Models\User;
use App\Services\Rbac\PlatformAuthorization;
use App\Services\Rbac\ProviderAuthorization;
use App\Support\Parking\ParkingIdentifier;
use App\Support\Rbac\Permission;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

final class ReportAvailabilityAction
{
    public static function make(): Action
    {
        return Action::make('reportAvailability')
            ->label(__('Report availability'))
            ->icon('heroicon-o-clipboard-document-check')
            ->color('primary')

            ->authorize(
                function (ParkingFacility|StreetParking $record): bool {
                    $user = auth()->user();

                    if (! $user instanceof User) {
                        return false;
                    }

                    if (app(PlatformAuthorization::class)->isAdmin($user)) {
                        return true;
                    }

                    return app(ProviderAuthorization::class)->allows(
                        $user,
                        $record->parking_provider_id,
                        Permission::OccupancyReportCreate,
                    );
                },
            )

            ->schema([
                TextInput::make('available_spaces')
                    ->label(__('Available spaces'))
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->maxValue(
                        fn (ParkingFacility|StreetParking $record): ?int => $record->capacity
                    )
                    ->helperText(
                        fn (ParkingFacility|StreetParking $record): ?string => $record->capacity === null
                            ? __('Set parking capacity before reporting availability.')
                            : null
                    )
                    ->required(),
            ])

            ->disabled(
                fn (ParkingFacility|StreetParking $record): bool => $record->capacity === null
            )

            ->fillForm(
                fn (ParkingFacility|StreetParking $record): array => [
                    'available_spaces' => $record->available_spaces,
                ],
            )

            ->modalHeading(__('Report parking availability'))
            ->modalSubmitActionLabel(__('Report availability'))
            ->action(function (ParkingFacility|StreetParking $record, array $data): void {
                app(ReportParkingAvailability::class)->execute(
                    new ReportParkingAvailabilityData(
                        parkingIdentifier: ParkingIdentifier::for($record),
                        availableSpaces: (int) $data['available_spaces'],
                    ),
                    source: ParkingSource::OPERATOR,
                    userId: auth()->id(),
                );

                Notification::make()
                    ->success()
                    ->title(__('Availability reported'))
                    ->body(__('The current parking availability has been updated.'))
                    ->send();
            });

    }
}
