<?php

use App\Enums\ParkingFacilityType;
use App\Enums\ParkingStatus;
use App\Filament\Resources\ParkingFacilities\Pages\CreateParkingFacility;
use App\Models\ParkingFacility;
use App\Models\ParkingProvider;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Support\ProviderTestContext;

$validFacilityForm = static fn(array $overrides = []): array => array_replace_recursive([
    'name' => 'Police Bazaar Parking',
    'type' => ParkingFacilityType::PUBLIC ->value,
    'status' => ParkingStatus::ACTIVE->value,
    'capacity' => 100,
    'location' => [
        'address_line1' => 'Police Bazaar',
        'locality' => 'Shillong',
        'administrative_area' => 'Meghalaya',
        'postal_code' => '793001',
        'country_code' => 'IN',
        'latitude' => 25.5788,
        'longitude' => 91.8933,
    ],
], $overrides);

$grantPlatformAdmin = static function (User $user): void {
    $registrar = app(PermissionRegistrar::class);
    $previousTeamId = $registrar->getPermissionsTeamId();

    try {
        $registrar->setPermissionsTeamId(null);

        $role = Role::findOrCreate('platform-admin', 'web');

        $user->unsetRelation('roles');
        $user->assignRole($role);
    } finally {
        $registrar->setPermissionsTeamId($previousTeamId);

        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');
    }
};

it('creates a parking facility using the selected provider in the admin panel', function () use ($validFacilityForm, $grantPlatformAdmin) {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $user = User::factory()->create();

    $grantPlatformAdmin($user);

    $this->actingAs($user);

    $provider = ParkingProvider::factory()->create();

    Livewire::test(CreateParkingFacility::class)
        ->fillForm($validFacilityForm([
            'parking_provider_id' => $provider->getKey(),
        ]))
        ->call('create')
        ->assertHasNoFormErrors();

    $facility = ParkingFacility::query()
        ->where('name', 'Police Bazaar Parking')
        ->firstOrFail();

    expect($facility->parking_provider_id)->toBe($provider->getKey())
        ->and($facility->location_id)->not->toBeNull();
});

it('creates a parking facility for the current provider tenant', function () use ($validFacilityForm) {
    Filament::setCurrentPanel('provider');

    $context = ProviderTestContext::create()->actingAsUser();

    Livewire::test(CreateParkingFacility::class)
        ->fillForm($validFacilityForm())
        ->call('create')
        ->assertHasNoFormErrors();

    $facility = ParkingFacility::query()
        ->where('name', 'Police Bazaar Parking')
        ->firstOrFail();

    expect($facility->parking_provider_id)->toBe($context->provider->getKey())
        ->and($facility->location_id)->not->toBeNull();
});

it('ignores an injected provider id and assigns the current provider tenant', function () use ($validFacilityForm) {
    Filament::setCurrentPanel('provider');

    $context = ProviderTestContext::create()->actingAsUser();

    $otherProvider = ParkingProvider::factory()->create([
        'is_active' => true,
    ]);

    Livewire::test(CreateParkingFacility::class)
        ->fillForm($validFacilityForm([
            'name' => 'Provider Parking',
            'capacity' => 50,
            // Simulate a forged request even though the provider form omits this field.
            'parking_provider_id' => $otherProvider->getKey(),
            'location' => [
                'address_line1' => null,
                'address_line2' => null,
                'locality' => null,
                'administrative_area' => null,
                'postal_code' => null,
            ],
        ]))
        ->call('create')
        ->assertHasNoFormErrors();

    $facility = ParkingFacility::query()
        ->where('name', 'Provider Parking')
        ->firstOrFail();

    expect($facility->parking_provider_id)->toBe($context->provider->getKey())
        ->and($facility->parking_provider_id)->not->toBe($otherProvider->getKey());
});

it('defaults the location country to India', function () use ($validFacilityForm) {
    Filament::setCurrentPanel('provider');

    ProviderTestContext::create()->actingAsUser();

    $form = $validFacilityForm([
        'name' => 'Shillong Parking',
        'capacity' => 50,
        'location' => [
            'address_line1' => null,
            'address_line2' => null,
            'locality' => null,
            'administrative_area' => null,
            'postal_code' => null,
        ],
    ]);

    // Omit the field so the form's default is exercised.
    unset($form['location']['country_code']);

    Livewire::test(CreateParkingFacility::class)
        ->fillForm($form)
        ->call('create')
        ->assertHasNoFormErrors();

    $facility = ParkingFacility::query()
        ->where('name', 'Shillong Parking')
        ->firstOrFail();

    expect($facility->location->country_code)->toBe('IN');
});