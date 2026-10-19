<?php

use App\Models\ParkingProvider;
use App\Models\ProviderMembership;
use App\Models\User;
use App\Services\Rbac\ProviderMembershipLookup;
use App\Services\Rbac\ProviderMembershipService;
use App\Services\Rbac\TeamRoleProvisioner;
use App\Support\Rbac\Role;

beforeEach(function () {
    $this->service = app(ProviderMembershipService::class);
    $this->provisioner = app(TeamRoleProvisioner::class);

    $this->provisioner->syncGlobalPermissions();
});

it('creates a membership and assigns the corresponding team role', function () {
    $user = User::factory()->create();
    $provider = ParkingProvider::factory()->create();

    $this->provisioner->syncTeamRoles($provider->getKey());

    $previousTeamId = getPermissionsTeamId();

    $membership = $this->service->assign(
        $user,
        $provider,
        Role::Manager,
    );

    expect($membership->user_id)->toBe($user->getKey())
        ->and($membership->parking_provider_id)->toBe($provider->getKey())
        ->and($membership->role)->toBe(Role::Manager->value)
        ->and(
            ProviderMembership::query()
                ->where('user_id', $user->getKey())
                ->where('parking_provider_id', $provider->getKey())
                ->count(),
        )->toBe(1)
        ->and(getPermissionsTeamId())->toBe($previousTeamId);

    setPermissionsTeamId($provider->getKey());

    $user->unsetRelation('roles');
    $user->unsetRelation('permissions');

    expect($user->hasRole(Role::Manager->value))->toBeTrue();
});

it('updates the membership and replaces the team role on reassignment', function () {
    $user = User::factory()->create();
    $provider = ParkingProvider::factory()->create();

    $this->provisioner->syncTeamRoles($provider->getKey());

    $this->service->assign($user, $provider, Role::Viewer);
    $this->service->assign($user, $provider, Role::Owner);

    expect(
        ProviderMembership::query()
            ->where('user_id', $user->getKey())
            ->where('parking_provider_id', $provider->getKey())
            ->count(),
    )->toBe(1);

    setPermissionsTeamId($provider->getKey());

    $user->unsetRelation('roles');
    $user->unsetRelation('permissions');

    expect($user->hasRole(Role::Owner->value))->toBeTrue()
        ->and($user->hasRole(Role::Viewer->value))->toBeFalse();
});

it('isolates role assignments between providers', function () {
    $user = User::factory()->create();
    $providerA = ParkingProvider::factory()->create();
    $providerB = ParkingProvider::factory()->create();

    $this->provisioner->syncTeamRoles($providerA->getKey());
    $this->provisioner->syncTeamRoles($providerB->getKey());

    $this->service->assign($user, $providerA, Role::Owner);
    $this->service->assign($user, $providerB, Role::Viewer);

    setPermissionsTeamId($providerA->getKey());
    $user->unsetRelation('roles');
    $user->unsetRelation('permissions');

    expect($user->hasRole(Role::Owner->value))->toBeTrue()
        ->and($user->hasRole(Role::Viewer->value))->toBeFalse();

    setPermissionsTeamId($providerB->getKey());
    $user->unsetRelation('roles');
    $user->unsetRelation('permissions');

    expect($user->hasRole(Role::Viewer->value))->toBeTrue()
        ->and($user->hasRole(Role::Owner->value))->toBeFalse();
});

it('removes the membership and revokes the role in that team', function () {
    $user = User::factory()->create();
    $provider = ParkingProvider::factory()->create();

    $this->provisioner->syncTeamRoles($provider->getKey());
    $this->service->assign($user, $provider, Role::Manager);

    expect($this->service->remove($user, $provider))->toBeTrue()
        ->and(
            ProviderMembership::query()
                ->where('user_id', $user->getKey())
                ->where('parking_provider_id', $provider->getKey())
                ->exists(),
        )->toBeFalse();

    setPermissionsTeamId($provider->getKey());
    $user->unsetRelation('roles');
    $user->unsetRelation('permissions');

    expect($user->hasRole(Role::Manager->value))->toBeFalse();
});

it('invalidates membership lookup after assignment and removal', function () {
    $user = User::factory()->create();
    $provider = ParkingProvider::factory()->create();

    $this->provisioner->syncTeamRoles($provider->getKey());

    $lookup = app(ProviderMembershipLookup::class);

    expect(
        $lookup->exists($user, $provider->getKey()),
    )->toBeFalse();

    $this->service->assign($user, $provider, Role::Manager);

    expect(
        $lookup->exists($user, $provider->getKey()),
    )->toBeTrue();

    $this->service->remove($user, $provider);

    expect(
        $lookup->exists($user, $provider->getKey()),
    )->toBeFalse();
});
