<?php

use App\Enums\AvailabilityStatus;
use App\Filament\Provider\Widgets\ParkingNeedingAttention;
use App\Models\ParkingFacility;
use App\Models\ParkingProvider;
use App\Models\StreetParking;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Tables\Table;
use Livewire\Livewire;

it('returns no records when there is no tenant', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Livewire::test(ParkingNeedingAttention::class);

    $table = $component->instance()->table(
        Table::make($component->instance()),
    );

    expect($table->getRecords())->toBeEmpty();
});

it('shows only parking needing attention for the current provider', function () {
    $user = User::factory()->create();

    $provider = ParkingProvider::factory()->create();
    $otherProvider = ParkingProvider::factory()->create();

    $this->actingAs($user);
    Filament::setTenant($provider);

    ParkingFacility::factory()->create([
        'parking_provider_id' => $provider->id,
        'name' => 'Missing Capacity',
        'capacity' => null,
    ]);

    ParkingFacility::factory()->create([
        'parking_provider_id' => $otherProvider->id,
        'name' => 'Other Provider Parking',
        'capacity' => null,
    ]);

    $component = Livewire::test(ParkingNeedingAttention::class);

    $table = $component->instance()->table(
        Table::make($component->instance()),
    );

    $records = $table->getRecords();

    expect($records)
        ->toHaveCount(1)
        ->and($records[0]['name'])->toBe('Missing Capacity')
        ->and($records[0]['type'])->toBe('Facility')
        ->and($records[0]['issue'])->toBe('Capacity not configured.');
});

it('flags parking whose availability has never been reported', function () {
    $user = User::factory()->create();
    $provider = ParkingProvider::factory()->create();

    $this->actingAs($user);
    Filament::setTenant($provider);

    ParkingFacility::factory()->create([
        'parking_provider_id' => $provider->id,
        'name' => 'Never Reported',
        'capacity' => 20,
        'availability_updated_at' => null,
        'availability_status' => AvailabilityStatus::AVAILABLE,
    ]);

    $component = Livewire::test(ParkingNeedingAttention::class);

    $table = $component->instance()->table(
        Table::make($component->instance()),
    );

    $records = $table->getRecords();

    expect($records[0])
        ->name->toBe('Never Reported')
        ->type->toBe('Facility')
        ->issue->toBe('Availability never reported.');
});

it('flags parking with unknown availability', function () {
    $user = User::factory()->create();
    $provider = ParkingProvider::factory()->create();

    $this->actingAs($user);
    Filament::setTenant($provider);

    ParkingFacility::factory()->create([
        'parking_provider_id' => $provider->id,
        'name' => 'Unknown Availability',
        'capacity' => 20,
        'availability_status' => AvailabilityStatus::UNKNOWN,
        'availability_updated_at' => now(),
    ]);

    $component = Livewire::test(ParkingNeedingAttention::class);

    $table = $component->instance()->table(
        Table::make($component->instance()),
    );

    $records = $table->getRecords();

    expect($records[0]['name'])->toBe('Unknown Availability')
        ->and($records[0]['issue'])->toBe('Availability unknown.');
});

it('excludes parking that does not need attention', function () {
    $user = User::factory()->create();
    $provider = ParkingProvider::factory()->create();

    $this->actingAs($user);
    Filament::setTenant($provider);

    ParkingFacility::factory()->create([
        'parking_provider_id' => $provider->id,
        'name' => 'Healthy Parking',
        'capacity' => 50,
        'availability_status' => AvailabilityStatus::AVAILABLE,
        'availability_updated_at' => now(),
    ]);

    $component = Livewire::test(ParkingNeedingAttention::class);

    $table = $component->instance()->table(
        Table::make($component->instance()),
    );

    expect($table->getRecords())->toBeEmpty();
});

it('combines facilities and street parking', function () {
    $user = User::factory()->create();
    $provider = ParkingProvider::factory()->create();

    $this->actingAs($user);
    Filament::setTenant($provider);

    ParkingFacility::factory()->create([
        'parking_provider_id' => $provider->id,
        'name' => 'Parking Facility',
        'capacity' => null,
    ]);

    StreetParking::factory()->create([
        'parking_provider_id' => $provider->id,
        'name' => 'Street Parking',
        'capacity' => null,
    ]);

    $component = Livewire::test(ParkingNeedingAttention::class);

    $table = $component->instance()->table(
        Table::make($component->instance()),
    );

    $records = $table->getRecords();

    expect($records)->toHaveCount(2)
        ->and(collect($records)->pluck('name')->all())
        ->toEqualCanonicalizing([
            'Parking Facility',
            'Street Parking',
        ]);
});

it('limits the results to ten records', function () {
    $user = User::factory()->create();
    $provider = ParkingProvider::factory()->create();

    $this->actingAs($user);
    Filament::setTenant($provider);

    ParkingFacility::factory()
        ->count(6)
        ->create([
            'parking_provider_id' => $provider->id,
            'capacity' => null,
        ]);

    StreetParking::factory()
        ->count(6)
        ->create([
            'parking_provider_id' => $provider->id,
            'capacity' => null,
        ]);

    $component = Livewire::test(ParkingNeedingAttention::class);

    $table = $component->instance()->table(
        Table::make($component->instance()),
    );

    expect($table->getRecords())->toHaveCount(10);
});
