<?php

use App\Models\ParkingProvider;
use App\Models\ProviderMembership;
use App\Models\User;
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
