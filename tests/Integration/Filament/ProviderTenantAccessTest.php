<?php

use App\Models\ParkingProvider;
use App\Models\ProviderMembership;
use App\Models\User;
use App\Services\Rbac\ProviderMembershipLookup;
use Filament\Facades\Filament;
use Filament\Panel;
use Mockery;

it('returns providers the user is a member of', function () {
    $user = User::factory()->create();

    $providerOne = ParkingProvider::factory()->create();
    $providerTwo = ParkingProvider::factory()->create();

    ProviderMembership::create([
        'user_id' => $user->id,
        'parking_provider_id' => $providerOne->id,
    ]);

    ProviderMembership::create([
        'user_id' => $user->id,
        'parking_provider_id' => $providerTwo->id,
    ]);

    $tenants = $user->getTenants(
        Mockery::mock(Panel::class),
    );

    expect($tenants)
        ->toHaveCount(2)
        ->pluck('id')
        ->toContain($providerOne->id, $providerTwo->id);
});

it('returns an empty collection when the user has no provider memberships', function () {
    $user = User::factory()->create();

    $tenants = $user->getTenants(
        Mockery::mock(Panel::class),
    );

    expect($tenants)->toBeEmpty();
});

it('allows access to an active provider when the user is a member', function () {
    $user = User::factory()->create();

    $provider = ParkingProvider::factory()->create([
        'is_active' => true,
    ]);

    ProviderMembership::create([
        'user_id' => $user->id,
        'parking_provider_id' => $provider->id,
    ]);

    expect($user->canAccessTenant($provider))->toBeTrue();
});

it('denies access to an inactive provider even when the user is a member', function () {
    $user = User::factory()->create();

    $provider = ParkingProvider::factory()->create([
        'is_active' => false,
    ]);

    ProviderMembership::create([
        'user_id' => $user->id,
        'parking_provider_id' => $provider->id,
    ]);

    expect($user->canAccessTenant($provider))->toBeFalse();
});

it('denies access when the user is not a member of the provider', function () {
    $user = User::factory()->create();

    $provider = ParkingProvider::factory()->create([
        'is_active' => true,
    ]);

    expect($user->canAccessTenant($provider))->toBeFalse();
});

it('denies access to a model that is not a parking provider', function () {
    $user = User::factory()->create();

    $otherUser = User::factory()->create();

    expect($user->canAccessTenant($otherUser))->toBeFalse();
});

it('reuses discovered memberships for tenant access checks', function () {
    $user = User::factory()->create();

    $provider = ParkingProvider::factory()->create([
        'is_active' => true,
    ]);

    ProviderMembership::create([
        'user_id' => $user->id,
        'parking_provider_id' => $provider->id,
    ]);

    $lookup = app(ProviderMembershipLookup::class);

    // Prime the request-scoped membership cache.
    expect($lookup->providerIds($user))
        ->toContain($provider->getKey());

    // These checks use the same membership data.
    expect($user->canAccessPanel(
        Filament::getPanel('provider'),
    ))->toBeTrue()
        ->and($user->canAccessTenant($provider))->toBeTrue();
});

it('invalidates cached membership IDs when a membership is removed', function () {
    $user = User::factory()->create();

    $provider = ParkingProvider::factory()->create([
        'is_active' => true,
    ]);

    ProviderMembership::create([
        'user_id' => $user->id,
        'parking_provider_id' => $provider->id,
    ]);

    $lookup = app(ProviderMembershipLookup::class);

    expect($lookup->exists($user, $provider->getKey()))
        ->toBeTrue();

    $lookup->forget($user);

    expect($lookup->exists($user, $provider->getKey()))
        ->toBeTrue();
});
