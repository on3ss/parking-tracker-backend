<?php

use App\Models\ParkingArea;
use App\Models\ParkingFacility;
use App\Models\User;
use App\Policies\ParkingAreaPolicy;
use App\Services\Rbac\TeamRoleProvisioner;
use App\Support\Rbac\PlatformRole;
use App\Support\Rbac\Role;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\Support\ProviderTestContext;

beforeEach(function () {
    $this->policy = app(ParkingAreaPolicy::class);
});

it('allows a platform admin to manage parking areas without provider context', function () {
    app(TeamRoleProvisioner::class)->syncGlobalPermissions();

    $admin = User::factory()->create();

    $role = SpatieRole::query()->firstOrCreate([
        'name' => PlatformRole::Admin->value,
        'guard_name' => TeamRoleProvisioner::GUARD,
        config('permission.column_names.team_foreign_key') => null,
    ]);

    $admin->assignRole($role);

    $context = ProviderTestContext::create(Role::Owner);

    $facility = ParkingFacility::factory()->create([
        'parking_provider_id' => $context->provider->id,
    ]);

    $area = ParkingArea::factory()->create([
        'parking_facility_id' => $facility->id,
    ]);

    setPermissionsTeamId(null);

    expect($this->policy->viewAny($admin))->toBeTrue()
        ->and($this->policy->view($admin, $area))->toBeTrue()
        ->and($this->policy->create($admin))->toBeTrue()
        ->and($this->policy->update($admin, $area))->toBeTrue()
        ->and($this->policy->delete($admin, $area))->toBeTrue()
        ->and($this->policy->restore($admin, $area))->toBeTrue()
        ->and($this->policy->forceDelete($admin, $area))->toBeTrue();
});

it('denies access to another provider area when the active team conflicts', function () {
    $activeContext = ProviderTestContext::create(Role::Owner);
    $areaOwner = ProviderTestContext::create(Role::Owner);

    $facility = ParkingFacility::factory()->create([
        'parking_provider_id' => $areaOwner->provider->id,
    ]);

    $area = ParkingArea::factory()->create([
        'parking_facility_id' => $facility->id,
    ]);

    // The user belongs to the area owner, but the active team is different.
    setPermissionsTeamId($activeContext->provider->id);

    expect($this->policy->view($areaOwner->user, $area))->toBeFalse()
        ->and($this->policy->update($areaOwner->user, $area))->toBeFalse()
        ->and($this->policy->delete($areaOwner->user, $area))->toBeFalse();
});

it('denies access to an area when the user lacks membership in its provider', function () {
    $owner = ProviderTestContext::create(Role::Owner);

    $facility = ParkingFacility::factory()->create([
        'parking_provider_id' => $owner->provider->id,
    ]);

    $area = ParkingArea::factory()->create([
        'parking_facility_id' => $facility->id,
    ]);

    $outsider = User::factory()->create();

    setPermissionsTeamId($owner->provider->id);

    expect($this->policy->view($outsider, $area))->toBeFalse()
        ->and($this->policy->update($outsider, $area))->toBeFalse()
        ->and($this->policy->delete($outsider, $area))->toBeFalse();
});
