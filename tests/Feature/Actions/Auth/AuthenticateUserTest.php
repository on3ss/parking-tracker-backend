<?php

use App\Actions\Auth\AuthenticateUser;
use App\Data\Auth\LoginData;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('authenticates a user and creates a token', function () {
    $user = User::factory()->create([
        'email' => 'john@example.com',
        'password' => Hash::make('secret123'),
    ]);

    $result = app(AuthenticateUser::class)->execute(
        new LoginData(
            email: 'john@example.com',
            password: 'secret123',
            deviceName: 'test-device',
        ),
    );

    expect($result['user']->is($user))->toBeTrue()
        ->and($result['token'])->toBeString()
        ->not->toBeEmpty();

    expect($user->tokens()->count())->toBe(1);
});
