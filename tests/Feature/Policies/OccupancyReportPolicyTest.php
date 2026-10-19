<?php

use App\Models\OccupancyReport;
use App\Models\ParkingFacility;
use App\Models\StreetParking;
use App\Models\User;
use App\Policies\OccupancyReportPolicy;
use App\Services\Rbac\TeamRoleProvisioner;
use App\Support\Rbac\PlatformRole;
use App\Support\Rbac\Role;
use Mockery;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\Support\ProviderTestContext;

beforeEach(function () {
    $this->policy = app(OccupancyReportPolicy::class);
});

/**
 * Create a report whose parent belongs to the given provider.
 */
function facilityReportFor(ProviderTestContext $context): OccupancyReport
{
    $facility = ParkingFacility::factory()->create([
        'parking_provider_id' => $context->provider->id,
    ]);

    return OccupancyReport::factory()
        ->forFacility($facility)
        ->create();
}

function streetParkingReportFor(ProviderTestContext $context): OccupancyReport
{
    $streetParking = StreetParking::factory()->create([
        'parking_provider_id' => $context->provider->id,
    ]);

    return OccupancyReport::factory()
        ->forStreetParking($streetParking)
        ->create();
}

it('allows a provider member with view permission to view reports', function () {
    $context = ProviderTestContext::create(Role::Manager)->useTeamContext();
    $report = facilityReportFor($context);

    expect($this->policy->viewAny($context->user))->toBeTrue()
        ->and($this->policy->view($context->user, $report))->toBeTrue();
});

it('denies report viewing without the required provider permission', function () {
    $context = ProviderTestContext::create(Role::Viewer)->useTeamContext();
    $report = facilityReportFor($context);

    expect($this->policy->viewAny($context->user))->toBeTrue()
        ->and($this->policy->view($context->user, $report))->toBeTrue();
});

it('denies viewing a report belonging to another provider', function () {
    $member = ProviderTestContext::create(Role::Manager)->useTeamContext();
    $otherProvider = ProviderTestContext::create(Role::Owner);
    $report = facilityReportFor($otherProvider);

    expect($this->policy->view($member->user, $report))->toBeFalse();
});

it('denies report access when the user has no current provider context', function () {
    $context = ProviderTestContext::create(Role::Manager);
    $report = facilityReportFor($context);

    setPermissionsTeamId(null);

    expect($this->policy->viewAny($context->user))->toBeFalse();
});

it('allows a manager to create reports in their provider context', function () {
    $context = ProviderTestContext::create(Role::Manager)->useTeamContext();

    expect($this->policy->create($context->user))->toBeTrue();
});

it('denies report creation to a viewer', function () {
    $context = ProviderTestContext::create(Role::Viewer)->useTeamContext();

    expect($this->policy->create($context->user))->toBeFalse();
});

it('always denies updating an immutable report', function () {
    $context = ProviderTestContext::create(Role::Owner)->useTeamContext();
    $report = facilityReportFor($context);

    expect($this->policy->update($context->user, $report))->toBeFalse();
});

it('allows a provider owner to delete a report', function () {
    $context = ProviderTestContext::create(Role::Owner)->useTeamContext();
    $report = facilityReportFor($context);

    expect($this->policy->delete($context->user, $report))->toBeTrue();
});

it('denies report deletion to a manager without delete permission', function () {
    $context = ProviderTestContext::create(Role::Manager)->useTeamContext();
    $report = facilityReportFor($context);

    expect($this->policy->delete($context->user, $report))->toBeFalse();
});

it('resolves report authorization through a street parking parent', function () {
    $context = ProviderTestContext::create(Role::Owner)->useTeamContext();
    $report = streetParkingReportFor($context);

    expect($this->policy->view($context->user, $report))->toBeTrue()
        ->and($this->policy->delete($context->user, $report))->toBeTrue();
});

it('resolves report authorization when the facility parent is soft deleted', function () {
    $context = ProviderTestContext::create(Role::Owner)->useTeamContext();
    $report = facilityReportFor($context);

    $report->parkingFacility()->delete();

    expect($this->policy->view($context->user, $report))->toBeTrue()
        ->and($this->policy->delete($context->user, $report))->toBeTrue();
});

it('resolves report authorization when the street parking parent is soft deleted', function () {
    $context = ProviderTestContext::create(Role::Owner)->useTeamContext();
    $report = streetParkingReportFor($context);

    $report->streetParking()->delete();

    expect($this->policy->view($context->user, $report))->toBeTrue()
        ->and($this->policy->delete($context->user, $report))->toBeTrue();
});

it('denies viewing and deleting a report with no resolvable parent', function () {
    $context = ProviderTestContext::create(Role::Owner)->useTeamContext();

    $report = Mockery::mock(OccupancyReport::class)->makePartial();

    $report->shouldReceive('getAttribute')
        ->with('parking_facility_id')
        ->andReturn(999999);

    $report->shouldReceive('getAttribute')
        ->with('street_parking_id')
        ->andReturn(null);

    $relationship = Mockery::mock(
        \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
    );

    $relationship->shouldReceive('withTrashed')
        ->twice()
        ->andReturnSelf();

    $relationship->shouldReceive('value')
        ->with('parking_provider_id')
        ->twice()
        ->andReturn(null);

    $report->shouldReceive('parkingFacility')
        ->twice()
        ->andReturn($relationship);

    expect($this->policy->view($context->user, $report))->toBeFalse()
        ->and($this->policy->delete($context->user, $report))->toBeFalse();
});

it('allows a platform admin to view reports without provider membership', function () {
    app(TeamRoleProvisioner::class)->syncGlobalPermissions();

    $user = User::factory()->create();

    $role = SpatieRole::query()->firstOrCreate([
        'name' => PlatformRole::Admin->value,
        'guard_name' => TeamRoleProvisioner::GUARD,
        config('permission.column_names.team_foreign_key') => null,
    ]);

    $user->assignRole($role);

    $context = ProviderTestContext::create(Role::Owner);
    $report = facilityReportFor($context);

    setPermissionsTeamId(null);

    expect($this->policy->viewAny($user))->toBeTrue()
        ->and($this->policy->view($user, $report))->toBeTrue()
        ->and($this->policy->create($user))->toBeTrue()
        ->and($this->policy->delete($user, $report))->toBeTrue();
});