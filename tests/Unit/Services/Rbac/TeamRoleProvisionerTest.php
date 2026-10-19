<?php

use App\Models\ParkingProvider;
use App\Services\Rbac\TeamRoleProvisioner;
use App\Support\Rbac\Permission as PermissionEnum;
use App\Support\Rbac\Role as RoleEnum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as SpatieRole;

function teamRole(
    int|string $providerId,
    RoleEnum $role,
): SpatieRole {
    $teamKey = config('permission.column_names.team_foreign_key');

    return SpatieRole::query()
        ->where('name', $role->value)
        ->where('guard_name', TeamRoleProvisioner::GUARD)
        ->where($teamKey, $providerId)
        ->firstOrFail();
}

function teamRolePermissionNames(SpatieRole $role): array
{
    return $role->permissions()
        ->pluck('name')
        ->sort()
        ->values()
        ->all();
}

it('provisions every configured role with exactly its declared permissions', function () {
    $provider = ParkingProvider::factory()->create();
    $provisioner = app(TeamRoleProvisioner::class);

    $provisioner->syncGlobalPermissions();
    $provisioner->syncTeamRoles($provider->id);

    foreach (RoleEnum::cases() as $roleEnum) {
        $role = teamRole($provider->id, $roleEnum);

        expect(teamRolePermissionNames($role))
            ->toBe(
                collect($roleEnum->permissionNames())
                    ->sort()
                    ->values()
                    ->all(),
            );
    }
});

it('removes stale permissions when a provider role is reprovisioned', function () {
    $provider = ParkingProvider::factory()->create();
    $provisioner = app(TeamRoleProvisioner::class);

    $provisioner->syncGlobalPermissions();
    $provisioner->syncTeamRoles($provider->id);

    $viewer = teamRole($provider->id, RoleEnum::Viewer);
    $removePermission = Permission::findByName(
        PermissionEnum::MembershipRemove->value,
        TeamRoleProvisioner::GUARD,
    );

    $previousTeamId = getPermissionsTeamId();

    try {
        setPermissionsTeamId($provider->id);
        $viewer->givePermissionTo($removePermission);
    } finally {
        setPermissionsTeamId($previousTeamId);
    }

    expect(teamRolePermissionNames($viewer))
        ->toContain(PermissionEnum::MembershipRemove->value);

    $provisioner->syncTeamRoles($provider->id);

    expect(teamRolePermissionNames($viewer))
        ->not->toContain(PermissionEnum::MembershipRemove->value);
});

it('does not modify another provider when provisioning a team', function () {
    $providerA = ParkingProvider::factory()->create();
    $providerB = ParkingProvider::factory()->create();

    $provisioner = app(TeamRoleProvisioner::class);

    $provisioner->syncGlobalPermissions();
    $provisioner->syncTeamRoles($providerA->id);
    $provisioner->syncTeamRoles($providerB->id);

    $viewerA = teamRole($providerA->id, RoleEnum::Viewer);
    $removePermission = Permission::findByName(
        PermissionEnum::MembershipRemove->value,
        TeamRoleProvisioner::GUARD,
    );

    $previousTeamId = getPermissionsTeamId();

    try {
        setPermissionsTeamId($providerA->id);
        $viewerA->givePermissionTo($removePermission);
    } finally {
        setPermissionsTeamId($previousTeamId);
    }

    $provisioner->syncTeamRoles($providerB->id);

    expect(teamRolePermissionNames($viewerA))
        ->toContain(PermissionEnum::MembershipRemove->value);

    expect(teamRolePermissionNames(
        teamRole($providerB->id, RoleEnum::Viewer),
    ))->not->toContain(PermissionEnum::MembershipRemove->value);
});

it('is idempotent when provisioning the same provider twice', function () {
    $provider = ParkingProvider::factory()->create();
    $provisioner = app(TeamRoleProvisioner::class);

    $provisioner->syncGlobalPermissions();
    $provisioner->syncTeamRoles($provider->id);

    $firstSnapshot = collect(RoleEnum::cases())
        ->mapWithKeys(fn (RoleEnum $role) => [
            $role->value => teamRolePermissionNames(
                teamRole($provider->id, $role),
            ),
        ])
        ->all();

    $provisioner->syncTeamRoles($provider->id);

    $secondSnapshot = collect(RoleEnum::cases())
        ->mapWithKeys(fn (RoleEnum $role) => [
            $role->value => teamRolePermissionNames(
                teamRole($provider->id, $role),
            ),
        ])
        ->all();

    expect($secondSnapshot)->toBe($firstSnapshot);
});
