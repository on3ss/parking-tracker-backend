<?php

use App\Filament\Provider\Resources\ParkingFacilities\ParkingFacilityResource;
use App\Models\ParkingFacility;
use App\Models\ParkingProvider;
use App\Models\ProviderMembership;
use App\Models\User;
use Filament\Facades\Filament;

it('allows a member to access their provider', function () {
    $user = User::factory()->create();
    $provider = ParkingProvider::factory()->create();

    ProviderMembership::factory()->create([
        'user_id' => $user->id,
        'parking_provider_id' => $provider->id,
    ]);

    expect($user->canAccessTenant($provider))->toBeTrue();
});

it('denies a user access to another provider', function () {
    $user = User::factory()->create();

    $providerA = ParkingProvider::factory()->create();
    $providerB = ParkingProvider::factory()->create();

    ProviderMembership::factory()->create([
        'user_id' => $user->id,
        'parking_provider_id' => $providerA->id,
    ]);

    expect($user->canAccessTenant($providerA))->toBeTrue();
    expect($user->canAccessTenant($providerB))->toBeFalse();
});

it('denies inactive providers', function () {
    $user = User::factory()->create();

    $provider = ParkingProvider::factory()->create([
        'is_active' => false,
    ]);

    ProviderMembership::factory()->create([
        'user_id' => $user->id,
        'parking_provider_id' => $provider->id,
    ]);

    expect($user->canAccessTenant($provider))->toBeFalse();
});

it('only queries parking facilities belonging to the current provider', function () {
    $providerA = ParkingProvider::factory()->create();
    $providerB = ParkingProvider::factory()->create();

    ParkingFacility::factory()->create([
        'parking_provider_id' => $providerA->id,
    ]);

    $facilityB = ParkingFacility::factory()->create([
        'parking_provider_id' => $providerB->id,
    ]);

    Filament::setTenant($providerA);

    expect(
        ParkingFacilityResource::getEloquentQuery()
            ->pluck('id')
    )->not->toContain($facilityB->id);
});