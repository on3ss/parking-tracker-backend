<?php

namespace App\Filament\Provider\Pages\Tenancy;

use App\Models\ParkingProvider;
use App\Models\User;
use App\Services\Rbac\ProviderMembershipService;
use App\Services\Rbac\TeamRoleProvisioner;
use App\Support\Rbac\Role;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;

class RegisterProvider extends RegisterTenant
{
    public static function canView(): bool
    {
        return Filament::auth()->check();
    }

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
        return DB::transaction(function () use ($data): ParkingProvider {
            /** @var User $user */
            $user = auth()->user();

            $provider = ParkingProvider::create($data);

            $provisioner = app(TeamRoleProvisioner::class);
            $provisioner->syncGlobalPermissions();
            $provisioner->syncTeamRoles($provider->getKey());

            app(ProviderMembershipService::class)->assign(
                $user,
                $provider,
                Role::Owner,
            );

            return $provider;
        });
    }
}
