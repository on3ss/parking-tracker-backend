<?php

namespace Tests\Support;

use App\Models\ParkingProvider;
use App\Models\ProviderMembership;
use App\Models\User;
use App\Services\Rbac\ProviderMembershipService;
use App\Services\Rbac\TeamRoleProvisioner;
use App\Support\Rbac\Role;
use Filament\Facades\Filament;

final class ProviderTestContext
{
    public readonly User $user;

    public readonly ParkingProvider $provider;

    public readonly Role $role;

    private function __construct(
        User $user,
        ParkingProvider $provider,
        Role $role,
    ) {
        $this->user = $user;
        $this->provider = $provider;
        $this->role = $role;
    }

    public static function create(
        Role $role = Role::Manager,
        ?User $user = null,
        array $providerAttributes = [],
    ): self {
        $user ??= User::factory()->create();

        $provider = ParkingProvider::factory()->create([
            ...$providerAttributes,
            'is_active' => true,
        ]);

        app(TeamRoleProvisioner::class)
            ->syncTeamRoles($provider->getKey());

        app(ProviderMembershipService::class)
            ->assign($user, $provider, $role);

        return new self($user, $provider, $role);
    }

    public function actingAsUser(): self
    {
        test()->actingAs($this->user);

        Filament::setTenant($this->provider);

        return $this->useTeamContext();
    }

    public function useTeamContext(): self
    {
        setPermissionsTeamId($this->provider->id);

        return $this;
    }

    public function membership(): ProviderMembership
    {
        return ProviderMembership::query()
            ->where('user_id', $this->user->id)
            ->where('parking_provider_id', $this->provider->id)
            ->firstOrFail();
    }
}
