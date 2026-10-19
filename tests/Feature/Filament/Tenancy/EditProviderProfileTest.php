<?php

use App\Enums\ParkingProviderType;
use App\Filament\Provider\Pages\Tenancy\EditProviderProfile;
use App\Support\Rbac\Role;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\Support\ProviderTestContext;

beforeEach(function () {
    Filament::setCurrentPanel('provider');
});

it('loads the current provider profile', function () {
    $context = ProviderTestContext::create(
        role: Role::Owner,
        providerAttributes: [
            'name' => 'Shillong Municipal Parking',
            'type' => ParkingProviderType::MUNICIPAL,
            'description' => 'Municipal parking facilities in Shillong.',
        ],
    )->actingAsUser();

    Livewire::test(EditProviderProfile::class)
        ->assertSchemaStateSet([
            'name' => 'Shillong Municipal Parking',
            'slug' => $context->provider->slug,
            'type' => ParkingProviderType::MUNICIPAL,
            'is_active' => true,
            'description' => '<p>Municipal parking facilities in Shillong.</p>',
        ]);
});

it('updates the provider profile fields exposed by the form', function () {
    $context = ProviderTestContext::create(
        role: Role::Owner,
        providerAttributes: [
            'name' => 'Old Provider Name',
            'type' => ParkingProviderType::MUNICIPAL,
            'description' => 'Old description.',
        ],
    )->actingAsUser();

    $provider = $context->provider;
    $originalSlug = $provider->slug;

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
    $context = ProviderTestContext::create(
        role: Role::Owner,
        providerAttributes: [
            'name' => 'Shillong Parking',
        ],
    )->actingAsUser();

    $provider = $context->provider;
    $originalSlug = $provider->slug;

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

it('denies the profile page to roles without provider update permission', function () {
    ProviderTestContext::create(role: Role::Manager)->actingAsUser();

    Livewire::test(EditProviderProfile::class)->assertNotFound();
});
