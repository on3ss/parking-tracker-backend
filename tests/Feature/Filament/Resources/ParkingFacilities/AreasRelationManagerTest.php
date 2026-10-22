<?php

use App\Filament\Resources\ParkingFacilities\Pages\EditParkingFacility;
use App\Filament\Resources\ParkingFacilities\RelationManagers\AreasRelationManager;
use App\Models\ParkingArea;
use App\Models\ParkingFacility;
use App\Support\Rbac\Role;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\Support\ProviderTestContext;

beforeEach(function (): void {
    Filament::setCurrentPanel('provider');
});

/**
 * Create two facilities under the same provider and an area for each.
 *
 * The same-provider setup is intentional: provider-level authorization
 * alone would allow access to both areas.
 *
 * @return array{
 *     facility: ParkingFacility,
 *     ownArea: ParkingArea,
 *     otherFacility: ParkingFacility,
 *     otherArea: ParkingArea
 * }
 */
function areasForRelationManagerBoundary(
    ProviderTestContext $context,
): array {
    $facility = ParkingFacility::factory()->create([
        'parking_provider_id' => $context->provider->getKey(),
    ]);

    $otherFacility = ParkingFacility::factory()->create([
        'parking_provider_id' => $context->provider->getKey(),
    ]);

    return [
        'facility' => $facility,
        'ownArea' => ParkingArea::factory()->create([
            'parking_facility_id' => $facility->getKey(),
        ]),
        'otherFacility' => $otherFacility,
        'otherArea' => ParkingArea::factory()->create([
            'parking_facility_id' => $otherFacility->getKey(),
        ]),
    ];
}

it('does not expose another facility area through the current relation manager', function () {
    $context = ProviderTestContext::create(Role::Owner)
        ->actingAsUser();

    $records = areasForRelationManagerBoundary($context);

    Livewire::test(AreasRelationManager::class, [
        'ownerRecord' => $records['facility'],
        'pageClass' => EditParkingFacility::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$records['ownArea']])
        ->assertCanNotSeeTableRecords([$records['otherArea']]);
});

it('shows only areas belonging to the current facility', function () {
    $context = ProviderTestContext::create(Role::Owner)
        ->actingAsUser();

    $records = areasForRelationManagerBoundary($context);

    Livewire::test(AreasRelationManager::class, [
        'ownerRecord' => $records['facility'],
        'pageClass' => EditParkingFacility::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$records['ownArea']])
        ->assertCanNotSeeTableRecords([$records['otherArea']]);
});
