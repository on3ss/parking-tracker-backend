<?php

use App\Filament\RelationManagers\OccupancyReportsRelationManager;
use App\Filament\Resources\StreetParkings\StreetParkingResource;
use App\Models\StreetParking;
use Filament\Facades\Filament;

it('uses the street parking model', function () {
    expect(StreetParkingResource::getModel())
        ->toBe(StreetParking::class);
});

it('uses name as the record title', function () {
    expect(StreetParkingResource::getRecordTitleAttribute())
        ->toBe('name');
});

it('returns the street parking pages', function () {
    $pages = StreetParkingResource::getPages();

    expect($pages)
        ->toHaveKeys([
            'index',
            'create',
            'view',
            'edit',
        ]);
});

it('returns the occupancy reports relation manager', function () {
    $relations = StreetParkingResource::getRelations();

    expect($relations)
        ->toContain(
            OccupancyReportsRelationManager::class,
        );
});

it('builds the admin resource configuration', function () {
    Filament::setCurrentPanel('admin');

    expect(StreetParkingResource::getPages())
        ->not->toBeEmpty();
});

it('builds the provider resource configuration', function () {
    Filament::setCurrentPanel('provider');

    expect(StreetParkingResource::getPages())
        ->not->toBeEmpty();
});
