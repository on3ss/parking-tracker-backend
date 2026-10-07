<?php

use App\Filament\Provider\Widgets\RecentOccupancyReports;
use App\Models\OccupancyReport;
use App\Models\ParkingFacility;
use App\Models\ParkingProvider;
use App\Models\StreetParking;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Tables\Table;
use Livewire\Livewire;

it('shows no reports when there is no tenant', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Livewire::test(RecentOccupancyReports::class);

    $table = $component->instance()->table(
        Table::make($component->instance()),
    );

    expect($table->getQuery()->count())->toBe(0);
});

it('shows recent reports for the current provider', function () {
    $user = User::factory()->create();
    $provider = ParkingProvider::factory()->create();

    $this->actingAs($user);
    Filament::setTenant($provider);

    $facility = ParkingFacility::factory()->create([
        'parking_provider_id' => $provider->id,
        'name' => 'Central Parking',
    ]);

    $streetParking = StreetParking::factory()->create([
        'parking_provider_id' => $provider->id,
        'name' => 'Police Bazaar Street Parking',
    ]);

    OccupancyReport::factory()->create([
        'parking_facility_id' => $facility->id,
        'available_spaces' => 10,
        'reported_at' => now()->subMinutes(10),
    ]);

    OccupancyReport::factory()->create([
        'street_parking_id' => $streetParking->id,
        'available_spaces' => 5,
        'reported_at' => now(),
    ]);

    $component = Livewire::test(RecentOccupancyReports::class);

    $table = $component->instance()->table(
        Table::make($component->instance()),
    );

    expect($table->getQuery()->count())->toBe(2);

    $reports = $table->getQuery()->get();

    expect($reports->pluck('id'))
        ->toHaveCount(2);
});

it('excludes reports belonging to another provider', function () {
    $user = User::factory()->create();

    $provider = ParkingProvider::factory()->create();
    $otherProvider = ParkingProvider::factory()->create();

    $this->actingAs($user);
    Filament::setTenant($provider);

    $facility = ParkingFacility::factory()->create([
        'parking_provider_id' => $provider->id,
    ]);

    $otherFacility = ParkingFacility::factory()->create([
        'parking_provider_id' => $otherProvider->id,
    ]);

    OccupancyReport::factory()->create([
        'parking_facility_id' => $facility->id,
    ]);

    OccupancyReport::factory()->create([
        'parking_facility_id' => $otherFacility->id,
    ]);

    $component = Livewire::test(RecentOccupancyReports::class);

    $table = $component->instance()->table(
        Table::make($component->instance()),
    );

    expect($table->getQuery()->count())->toBe(1)
        ->and(
            $table->getQuery()->first()->parking_facility_id
        )->toBe($facility->id);
});

it('orders reports by most recent first', function () {
    $user = User::factory()->create();
    $provider = ParkingProvider::factory()->create();

    $this->actingAs($user);
    Filament::setTenant($provider);

    $facility = ParkingFacility::factory()->create([
        'parking_provider_id' => $provider->id,
    ]);

    $older = OccupancyReport::factory()->create([
        'parking_facility_id' => $facility->id,
        'reported_at' => now()->subHour(),
    ]);

    $newer = OccupancyReport::factory()->create([
        'parking_facility_id' => $facility->id,
        'reported_at' => now(),
    ]);

    $component = Livewire::test(RecentOccupancyReports::class);

    $table = $component->instance()->table(
        Table::make($component->instance()),
    );

    expect($table->getQuery()->pluck('id')->all())
        ->toBe([
            $newer->id,
            $older->id,
        ]);
});

it('resolves the parking facility name for a facility report', function () {
    $user = User::factory()->create();
    $provider = ParkingProvider::factory()->create();

    $this->actingAs($user);
    Filament::setTenant($provider);

    $facility = ParkingFacility::factory()->create([
        'parking_provider_id' => $provider->id,
        'name' => 'Central Parking',
    ]);

    $report = OccupancyReport::factory()->create([
        'parking_facility_id' => $facility->id,
    ]);

    Livewire::test(RecentOccupancyReports::class)
        ->assertTableColumnStateSet(
            'parking_name',
            'Central Parking',
            $report,
        );
});

it('resolves the street parking name for a street report', function () {
    $user = User::factory()->create();
    $provider = ParkingProvider::factory()->create();

    $this->actingAs($user);
    Filament::setTenant($provider);

    $streetParking = StreetParking::factory()->create([
        'parking_provider_id' => $provider->id,
        'name' => 'Police Bazaar Street Parking',
    ]);

    $report = OccupancyReport::factory()->create([
        'parking_facility_id' => null,
        'street_parking_id' => $streetParking->id,
    ]);

    Livewire::test(RecentOccupancyReports::class)
        ->assertTableColumnStateSet(
            'parking_name',
            'Police Bazaar Street Parking',
            $report,
        );
});

it('uses the system placeholder when a report has no user', function () {
    $user = User::factory()->create();
    $provider = ParkingProvider::factory()->create();

    $this->actingAs($user);
    Filament::setTenant($provider);

    $facility = ParkingFacility::factory()->create([
        'parking_provider_id' => $provider->id,
    ]);

    $report = OccupancyReport::factory()->create([
        'parking_facility_id' => $facility->id,
        'user_id' => null,
    ]);

    expect($report->user)->toBeNull();

    $component = Livewire::test(RecentOccupancyReports::class);

    $table = $component->instance()->table(
        Table::make($component->instance()),
    );

    $record = $table->getQuery()->findOrFail($report->id);

    expect($record->user)->toBeNull();
});
