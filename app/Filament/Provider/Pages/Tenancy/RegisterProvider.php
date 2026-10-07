<?php

namespace App\Filament\Provider\Pages\Tenancy;

use App\Models\ParkingProvider;
use App\Models\ProviderMembership;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;

class RegisterProvider extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'Register provider';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255),
        ]);
    }

    protected function handleRegistration(array $data): ParkingProvider
    {
        $provider = ParkingProvider::create($data);

        ProviderMembership::create([
            'parking_provider_id' => $provider->id,
            'user_id' => auth()->id(),
        ]);

        return $provider;
    }
}
