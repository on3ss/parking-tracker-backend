<?php

use App\Filament\Provider\Pages\Tenancy\RegisterProvider;
use App\Models\ParkingProvider;
use App\Models\ProviderMembership;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

it('registers a provider and creates a membership for the authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Filament::setCurrentPanel(
        Filament::getPanel('provider'),
    );

    Livewire::test(RegisterProvider::class)
        ->fillForm([
            'name' => 'Shillong Municipal Parking',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    $provider = ParkingProvider::query()
        ->where('name', 'Shillong Municipal Parking')
        ->firstOrFail();

    expect(ProviderMembership::query())
        ->where('user_id', $user->id)
        ->where('parking_provider_id', $provider->id)
        ->exists()
        ->toBeTrue();
});
