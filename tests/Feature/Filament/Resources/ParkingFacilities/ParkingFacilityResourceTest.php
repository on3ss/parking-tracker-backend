<?php

use App\Filament\RelationManagers\OccupancyReportsRelationManager;
use App\Filament\Resources\ParkingFacilities\ParkingFacilityResource;
use App\Models\ParkingFacility;
use App\Models\ParkingProvider;
use App\Models\ProviderMembership;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

it('uses the parking facility model', function () {
    expect(ParkingFacilityResource::getModel())
        ->toBe(ParkingFacility::class);
});

it('uses the parking facility name as the record title', function () {
    expect(ParkingFacilityResource::getRecordTitleAttribute())
        ->toBe('name');
});

it('includes the provider field in the admin form', function () {
    Filament::setCurrentPanel('admin');

    $schema = ParkingFacilityResource::form(
        app(Schema::class),
    );

    expect($schema->getComponents())
        ->not->toBeEmpty();
});

it('excludes the provider field from the provider form', function () {
    Filament::setCurrentPanel('provider');

    $schema = ParkingFacilityResource::form(
        app(Schema::class),
    );

    expect($schema->getComponents())
        ->not->toBeEmpty();
});

it('defines the expected resource pages', function () {
    $pages = ParkingFacilityResource::getPages();

    expect($pages)
        ->toHaveKeys([
            'index',
            'create',
            'view',
            'edit',
        ]);
});

it('includes the occupancy reports relation manager', function () {
    expect(ParkingFacilityResource::getRelations())
        ->toContain(
            OccupancyReportsRelationManager::class,
        );
});

it('includes soft deleted records in the admin route binding query', function () {
    Filament::setCurrentPanel('admin');

    $active = ParkingFacility::factory()->create();

    $deleted = ParkingFacility::factory()->create();
    $deleted->delete();

    expect(
        ParkingFacilityResource::getRecordRouteBindingEloquentQuery()
            ->whereKey($active->getKey())
            ->exists()
    )->toBeTrue();

    expect(
        ParkingFacilityResource::getRecordRouteBindingEloquentQuery()
            ->whereKey($deleted->getKey())
            ->exists()
    )->toBeTrue();
});

it('excludes soft deleted records from the provider route binding query', function () {
    Filament::setCurrentPanel('provider');

    $facility = ParkingFacility::factory()->create();

    $facility->delete();

    expect(
        ParkingFacilityResource::getRecordRouteBindingEloquentQuery()
            ->whereKey($facility->getKey())
            ->exists()
    )->toBeFalse();
});

it('uses the normal tenant scoped route binding query for the provider panel', function () {
    Filament::setCurrentPanel('provider');

    $query = ParkingFacilityResource::getRecordRouteBindingEloquentQuery();

    expect($query)
        ->toBeInstanceOf(Builder::class);
});

it('only resolves parking facilities belonging to the current provider', function () {
    $user = User::factory()->create();

    $providerOne = ParkingProvider::factory()->create([
        'is_active' => true,
    ]);

    $providerTwo = ParkingProvider::factory()->create([
        'is_active' => true,
    ]);

    ProviderMembership::create([
        'user_id' => $user->id,
        'parking_provider_id' => $providerOne->id,
    ]);

    $facilityOne = ParkingFacility::factory()->create([
        'parking_provider_id' => $providerOne->id,
    ]);

    $facilityTwo = ParkingFacility::factory()->create([
        'parking_provider_id' => $providerTwo->id,
    ]);

    $this->actingAs($user);

    Filament::setCurrentPanel('provider');
    Filament::setTenant($providerOne);

    $query = ParkingFacilityResource::getRecordRouteBindingEloquentQuery();

    expect($query->whereKey($facilityOne)->exists())
        ->toBeTrue();

    expect($query->whereKey($facilityTwo)->exists())
        ->toBeFalse();
});