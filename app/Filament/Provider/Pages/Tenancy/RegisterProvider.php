<?php

namespace App\Filament\Provider\Pages\Tenancy;

use App\Models\ParkingProvider;
use App\Models\ProviderMembership;
use App\Services\Rbac\ProviderMembershipService;
use App\Services\Rbac\TeamRoleProvisioner;
use App\Support\Rbac\Concerns\InteractsWithTeams;
use App\Support\Rbac\Role;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;

class RegisterProvider extends RegisterTenant
{
    use InteractsWithTeams;

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
            $user = auth()->user();
            $provisioner = app(TeamRoleProvisioner::class);

            $provider = ParkingProvider::create($data);

            // Idempotent — safe even if the seeder already ran at deploy time.
            $provisioner->provisionProvider($provider->id);

            app(ProviderMembershipService::class)->assign(
                $user,
                $provider,
                Role::Owner,
            );

            $this->withTeam(
                $provider->id,
                fn() => $user->syncRoles([Role::Owner->value]),
            );

            return $provider;
        });
    }
}
