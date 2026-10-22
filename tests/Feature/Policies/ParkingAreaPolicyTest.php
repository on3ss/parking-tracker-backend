<?php

use App\Models\ParkingArea;
use App\Models\ParkingFacility;
use App\Models\User;
use App\Policies\ParkingAreaPolicy;
use App\Support\Rbac\Permission;
use App\Support\Rbac\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Support\PlatformAdminTestHelper;
use Tests\Support\ProviderTestContext;

beforeEach(function () {
    $this->policy = app(ParkingAreaPolicy::class);
});

/**
 * Create a parking area owned by the provider in the test context.
 */
function parkingAreaForProvider(ProviderTestContext $context): ParkingArea
{
    $facility = ParkingFacility::factory()->create([
        'parking_provider_id' => $context->provider->getKey(),
    ]);

    return ParkingArea::factory()->create([
        'parking_facility_id' => $facility->getKey(),
    ]);
}

it('allows a provider manager to view, create and update areas', function () {
    $context = ProviderTestContext::create(Role::Manager)
        ->useTeamContext();

    $area = parkingAreaForProvider($context);

    expect($this->policy->viewAny($context->user))->toBeTrue()
        ->and($this->policy->view($context->user, $area))->toBeTrue()
        ->and($this->policy->create($context->user))->toBeTrue()
        ->and($this->policy->update($context->user, $area))->toBeTrue();
});

it('denies area deletion to a manager without delete permission', function () {
    $context = ProviderTestContext::create(Role::Manager)
        ->useTeamContext();

    $area = parkingAreaForProvider($context);

    expect($this->policy->delete($context->user, $area))->toBeFalse()
        ->and($this->policy->restore($context->user, $area))->toBeFalse()
        ->and($this->policy->forceDelete($context->user, $area))->toBeFalse()
        ->and(
            $this->policy->deleteForFacility(
                $context->user,
                $area->parkingFacility,
            ),
        )->toBeFalse();
});

it('allows a provider owner to delete, restore and force delete areas', function () {
    $context = ProviderTestContext::create(Role::Owner)
        ->useTeamContext();

    $area = parkingAreaForProvider($context);

    expect($this->policy->delete($context->user, $area))->toBeTrue()
        ->and($this->policy->restore($context->user, $area))->toBeTrue()
        ->and($this->policy->forceDelete($context->user, $area))->toBeTrue()
        ->and(
            $this->policy->deleteForFacility(
                $context->user,
                $area->parkingFacility,
            ),
        )->toBeTrue();
});

it('denies area creation and modification to a viewer', function () {
    $context = ProviderTestContext::create(Role::Viewer)
        ->useTeamContext();

    $area = parkingAreaForProvider($context);

    expect($this->policy->view($context->user, $area))->toBeTrue()
        ->and($this->policy->create($context->user))->toBeFalse()
        ->and($this->policy->createForFacility(
            $context->user,
            $area->parkingFacility,
        ))->toBeFalse()
        ->and($this->policy->update($context->user, $area))->toBeFalse()
        ->and($this->policy->delete($context->user, $area))->toBeFalse();
});

it('denies access to areas owned by another provider', function () {
    $member = ProviderTestContext::create(Role::Owner)
        ->useTeamContext();

    $otherProvider = ProviderTestContext::create(Role::Owner);
    $area = parkingAreaForProvider($otherProvider);

    expect($this->policy->view($member->user, $area))->toBeFalse()
        ->and($this->policy->update($member->user, $area))->toBeFalse()
        ->and($this->policy->delete($member->user, $area))->toBeFalse();
});

it('denies access to a provider area when the active team conflicts', function () {
    $areaOwner = ProviderTestContext::create(Role::Owner);
    $area = parkingAreaForProvider($areaOwner);

    $activeContext = ProviderTestContext::create(Role::Owner)
        ->useTeamContext();

    // The user belongs to the area provider, but another team is active.
    setPermissionsTeamId($activeContext->provider->getKey());

    expect($this->policy->view($areaOwner->user, $area))->toBeFalse()
        ->and($this->policy->update($areaOwner->user, $area))->toBeFalse()
        ->and($this->policy->delete($areaOwner->user, $area))->toBeFalse();
});

it('denies area access to a user without provider membership', function () {
    $owner = ProviderTestContext::create(Role::Owner)
        ->useTeamContext();

    $area = parkingAreaForProvider($owner);
    $outsider = User::factory()->create();

    expect($this->policy->viewAny($outsider))->toBeFalse()
        ->and($this->policy->view($outsider, $area))->toBeFalse()
        ->and($this->policy->update($outsider, $area))->toBeFalse()
        ->and($this->policy->delete($outsider, $area))->toBeFalse();
});

it('denies provider-scoped operations without an active team context', function () {
    $context = ProviderTestContext::create(Role::Owner);
    $area = parkingAreaForProvider($context);

    setPermissionsTeamId(null);

    expect($this->policy->viewAny($context->user))->toBeFalse()
        ->and($this->policy->create($context->user))->toBeFalse();
});

it('allows a platform admin to perform area operations without provider membership', function () {
    $admin = User::factory()->create();

    app(PlatformAdminTestHelper::class)->grant($admin);

    $context = ProviderTestContext::create(Role::Owner);
    $area = parkingAreaForProvider($context);
    $facility = $area->parkingFacility;

    // The platform admin has no provider membership or active team context.
    setPermissionsTeamId(null);

    expect($this->policy->viewAny($admin))->toBeTrue()
        ->and($this->policy->view($admin, $area))->toBeTrue()
        ->and($this->policy->create($admin))->toBeTrue()
        ->and($this->policy->createForFacility($admin, $facility))->toBeTrue()
        ->and($this->policy->update($admin, $area))->toBeTrue()
        ->and($this->policy->delete($admin, $area))->toBeTrue()
        ->and($this->policy->restore($admin, $area))->toBeTrue()
        ->and($this->policy->forceDelete($admin, $area))->toBeTrue()
        ->and($this->policy->deleteForFacility($admin, $facility))->toBeTrue();
});

it('denies area access when the required permission has been revoked', function () {
    $context = ProviderTestContext::create(Role::Owner)
        ->useTeamContext();

    $area = parkingAreaForProvider($context);
    $membership = $context->membership();

    $teamKey = config('permission.column_names.team_foreign_key');

    $role = Spatie\Permission\Models\Role::query()
        ->where('name', Role::Owner->value)
        ->where($teamKey, $context->provider->getKey())
        ->firstOrFail();

    $role->revokePermissionTo(Permission::AreaView->value);

    app(PermissionRegistrar::class)
        ->forgetCachedPermissions();

    $context->user->unsetRelation('roles');
    $context->user->unsetRelation('permissions');

    expect($this->policy->viewAny($context->user))->toBeFalse()
        ->and($this->policy->view($context->user, $area))->toBeFalse();
});
