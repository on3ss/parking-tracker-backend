<?php

use App\Models\User;
use App\Services\Rbac\ProviderAuthorization;
use App\Support\Rbac\Permission;
use App\Support\Rbac\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Support\ProviderTestContext;

beforeEach(function () {
    $this->authorization = app(ProviderAuthorization::class);
});

it('allows a provider member with the requested permission', function () {
    $context = ProviderTestContext::create(Role::Owner);

    expect($this->authorization->allows(
        $context->user,
        $context->provider->getKey(),
        Permission::FacilityView,
    ))->toBeTrue();
});

it('denies a user who has no membership in the requested provider', function () {
    $context = ProviderTestContext::create(Role::Owner);
    $outsider = User::factory()->create();

    setPermissionsTeamId($context->provider->getKey());

    expect($this->authorization->allows(
        $outsider,
        $context->provider->getKey(),
        Permission::FacilityView,
    ))->toBeFalse();
});

it('denies authorization when no provider ID is supplied', function () {
    $user = User::factory()->create();

    expect($this->authorization->allows(
        $user,
        null,
        Permission::FacilityView,
    ))->toBeFalse();
});

it('denies authorization when the active team conflicts with the requested provider', function () {
    $activeContext = ProviderTestContext::create(Role::Owner);
    $targetContext = ProviderTestContext::create(Role::Owner);

    setPermissionsTeamId($activeContext->provider->getKey());

    expect($this->authorization->allows(
        $targetContext->user,
        $targetContext->provider->getKey(),
        Permission::FacilityView,
    ))->toBeFalse();
});

it('restores the previous team context after authorization', function () {
    $context = ProviderTestContext::create(Role::Owner);

    setPermissionsTeamId(null);

    $result = $this->authorization->allows(
        $context->user,
        $context->provider->getKey(),
        Permission::FacilityView,
    );

    expect($result)->toBeTrue()
        ->and(getPermissionsTeamId())->toBeNull();
});

it('denies authorization when the member lacks the requested permission', function () {
    $context = ProviderTestContext::create(Role::Viewer);

    $teamKey = config('permission.column_names.team_foreign_key');

    $role = Spatie\Permission\Models\Role::query()
        ->where('name', Role::Viewer->value)
        ->where($teamKey, $context->provider->getKey())
        ->firstOrFail();

    $role->revokePermissionTo(Permission::FacilityView->value);

    app(PermissionRegistrar::class)
        ->forgetCachedPermissions();

    setPermissionsTeamId($context->provider->getKey());

    $context->user->unsetRelation('roles');
    $context->user->unsetRelation('permissions');

    expect($this->authorization->allows(
        $context->user,
        $context->provider->getKey(),
        Permission::FacilityView,
    ))->toBeFalse();
});
