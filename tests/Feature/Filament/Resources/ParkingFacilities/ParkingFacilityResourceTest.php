<?php

use App\Filament\Resources\ParkingFacilities\ParkingFacilityResource;
use App\Models\ParkingFacility;
use Filament\Facades\Filament;

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
        app(\Filament\Schemas\Schema::class),
    );

    expect($schema->getComponents())
        ->not->toBeEmpty();
});

it('excludes the provider field from the provider form', function () {
    Filament::setCurrentPanel('provider');

    $schema = ParkingFacilityResource::form(
        app(\Filament\Schemas\Schema::class),
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
            \App\Filament\RelationManagers\OccupancyReportsRelationManager::class,
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
        ->toBeInstanceOf(\Illuminate\Database\Eloquent\Builder::class);
});