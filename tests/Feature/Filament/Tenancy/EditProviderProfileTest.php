<?php

use App\Enums\ParkingProviderType;
use App\Filament\Pages\Tenancy\EditProviderProfile;
use App\Models\ParkingProvider;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

it('loads the current provider profile', function () {
    $user = User::factory()->create();

    $provider = ParkingProvider::factory()->create([
        'name' => 'Shillong Municipal Parking',
        'type' => ParkingProviderType::MUNICIPAL,
        'description' => 'Municipal parking facilities in Shillong.',
        'is_active' => true,
    ]);

    $this->actingAs($user);

    Filament::setTenant($provider);

    Livewire::test(EditProviderProfile::class)
        ->assertSchemaStateSet([
            'name' => 'Shillong Municipal Parking',
            'slug' => $provider->slug,
            'type' => ParkingProviderType::MUNICIPAL,
            'is_active' => true,
            'description' => '<p>Municipal parking facilities in Shillong.</p>',
        ]);
});

it('updates the provider profile fields exposed by the form', function () {
    $user = User::factory()->create();

    $provider = ParkingProvider::factory()->create([
        'name' => 'Old Provider Name',
        'type' => ParkingProviderType::MUNICIPAL,
        'description' => 'Old description.',
        'is_active' => true,
    ]);

    $originalSlug = $provider->slug;

    $this->actingAs($user);

    Filament::setTenant($provider);

    Livewire::test(EditProviderProfile::class)
        ->fillForm([
            'name' => 'Updated Provider Name',
            'slug' => 'should-not-change',
            'type' => ParkingProviderType::PRIVATE->value,
            'is_active' => false,
            'description' => 'Updated provider description.',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $provider->refresh();

    expect($provider->name)->toBe('Updated Provider Name')
        ->and($provider->type)->toBe(ParkingProviderType::PRIVATE)
        ->and($provider->is_active)->toBeFalse()
        ->and($provider->description)->toBe('<p>Updated provider description.</p>')
        ->and($provider->slug)->toBe($originalSlug);
});

it('does not allow the slug to be edited through the profile form', function () {
    $user = User::factory()->create();

    $provider = ParkingProvider::factory()->create([
        'name' => 'Shillong Parking',
    ]);

    $originalSlug = $provider->slug;

    $this->actingAs($user);

    Filament::setTenant($provider);

    Livewire::test(EditProviderProfile::class)
        ->fillForm([
            'name' => 'Updated Parking',
            'type' => $provider->type->value,
            'is_active' => true,
            'description' => $provider->description,
            'slug' => 'manually-changed-slug',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($provider->fresh()->slug)->toBe($originalSlug);
});
