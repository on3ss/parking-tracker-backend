<?php

use App\Models\ParkingProvider;
use App\Models\ProviderMembership;
use App\Models\User;
use App\Services\Rbac\ProviderMembershipService;
use App\Services\Rbac\TeamRoleProvisioner;
use App\Support\Rbac\Role;
use Spatie\Permission\Exceptions\RoleDoesNotExist;

beforeEach(function () {
    $provisioner = app(TeamRoleProvisioner::class);

    $provisioner->syncGlobalPermissions();
});

function provisionProviderRoles(ParkingProvider $provider): void
{
    app(TeamRoleProvisioner::class)->syncTeamRoles($provider->getKey());
}

/**
 * Read a user's Spatie roles under a particular provider team,
 * restoring the previous team context afterward.
 *
 * @return list<string>
 */
function rolesForProvider(User $user, ParkingProvider $provider): array
{
    $previousTeamId = getPermissionsTeamId();

    try {
        setPermissionsTeamId($provider->getKey());

        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');

        return $user->getRoleNames()->all();
    } finally {
        setPermissionsTeamId($previousTeamId);

        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');
    }
}

it('creates a domain membership and assigns the matching team role', function () {
    $user = User::factory()->create();
    $provider = ParkingProvider::factory()->create();

    provisionProviderRoles($provider);

    $previousTeamId = getPermissionsTeamId();

    $membership = app(ProviderMembershipService::class)->assign(
        $user,
        $provider,
        Role::Owner,
    );

    expect($membership)
        ->toBeInstanceOf(ProviderMembership::class)
        ->and($membership->user_id)->toBe($user->getKey())
        ->and($membership->parking_provider_id)->toBe($provider->getKey())
        ->and($membership->role)->toBe(Role::Owner->value)
        ->and(ProviderMembership::query()
            ->where('user_id', $user->getKey())
            ->where('parking_provider_id', $provider->getKey())
            ->count())->toBe(1)
        ->and(rolesForProvider($user, $provider))
        ->toBe([Role::Owner->value])
        ->and(getPermissionsTeamId())->toBe($previousTeamId);
});

it('updates the membership and replaces the role when reassigned', function () {
    $user = User::factory()->create();
    $provider = ParkingProvider::factory()->create();

    provisionProviderRoles($provider);

    $service = app(ProviderMembershipService::class);

    $service->assign($user, $provider, Role::Owner);
    $membership = $service->assign($user, $provider, Role::Viewer);

    expect($membership->fresh()->role)->toBe(Role::Viewer->value)
        ->and(ProviderMembership::query()
            ->where('user_id', $user->getKey())
            ->where('parking_provider_id', $provider->getKey())
            ->count())->toBe(1)
        ->and(rolesForProvider($user, $provider))
        ->toBe([Role::Viewer->value]);
});

it('keeps role assignments isolated between providers', function () {
    $user = User::factory()->create();
    $providerA = ParkingProvider::factory()->create();
    $providerB = ParkingProvider::factory()->create();

    provisionProviderRoles($providerA);
    provisionProviderRoles($providerB);

    $service = app(ProviderMembershipService::class);

    $service->assign($user, $providerA, Role::Owner);
    $service->assign($user, $providerB, Role::Viewer);

    expect(rolesForProvider($user, $providerA))
        ->toBe([Role::Owner->value])
        ->and(rolesForProvider($user, $providerB))
        ->toBe([Role::Viewer->value])
        ->and(ProviderMembership::query()
            ->where('user_id', $user->getKey())
            ->count())->toBe(2);
});

it('rolls back membership creation when team role assignment fails', function () {
    $user = User::factory()->create();
    $provider = ParkingProvider::factory()->create();

    // Deliberately do not provision this provider's team roles.
    $previousTeamId = getPermissionsTeamId();

    try {
        app(ProviderMembershipService::class)->assign(
            $user,
            $provider,
            Role::Owner,
        );

        $this->fail('Expected role assignment to fail.');
    } catch (RoleDoesNotExist) {
        // The service must roll back its membership write.
    }

    expect(ProviderMembership::query()
        ->where('user_id', $user->getKey())
        ->where('parking_provider_id', $provider->getKey())
        ->exists())->toBeFalse()
        ->and(getPermissionsTeamId())->toBe($previousTeamId);
});
