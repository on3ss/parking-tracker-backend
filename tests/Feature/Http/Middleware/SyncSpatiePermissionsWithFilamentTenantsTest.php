<?php

use App\Http\Middleware\SyncSpatiePermissionsWithFilamentTenants;
use App\Models\ParkingProvider;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\Request;

beforeEach(function () {
    Filament::setCurrentPanel('provider');
    $this->actingAs(User::factory()->create());
});

function runSyncMiddleware(): void
{
    app(SyncSpatiePermissionsWithFilamentTenants::class)->handle(
        Request::create('/test'),
        fn () => response('ok'),
    );
}

it('sets the team id from the current tenant and keeps it after the pipeline', function () {
    $provider = ParkingProvider::factory()->create();
    Filament::setTenant($provider);

    runSyncMiddleware();

    expect(getPermissionsTeamId())->toBe($provider->getKey());
});

it('clears a stale team id when there is no tenant', function () {
    setPermissionsTeamId(123);
    Filament::setTenant(null);

    runSyncMiddleware();

    expect(getPermissionsTeamId())->toBeNull();
});
