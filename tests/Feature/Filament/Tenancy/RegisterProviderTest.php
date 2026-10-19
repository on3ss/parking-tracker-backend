<?php

use App\Filament\Provider\Pages\Tenancy\RegisterProvider;
use App\Models\ParkingProvider;
use App\Models\ProviderMembership;
use App\Models\User;
use App\Support\Rbac\Role;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel('provider');
});

it('registers a provider and creates a membership for the authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(RegisterProvider::class)
        ->fillForm(['name' => 'Shillong Municipal Parking'])
        ->call('register')
        ->assertHasNoFormErrors();

    $provider = ParkingProvider::query()
        ->where('name', 'Shillong Municipal Parking')
        ->firstOrFail();

    expect(
        ProviderMembership::query()
            ->where('user_id', $user->id)
            ->where('parking_provider_id', $provider->id)
            ->where('role', Role::Owner->value)
            ->exists()
    )->toBeTrue();

    $previousTeamId = getPermissionsTeamId();

    try {
        setPermissionsTeamId($provider->id);
        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');

        expect($user->hasRole(Role::Owner->value))->toBeTrue();
    } finally {
        setPermissionsTeamId($previousTeamId);
    }

    expect($user->canAccessTenant($provider))->toBeTrue();
});