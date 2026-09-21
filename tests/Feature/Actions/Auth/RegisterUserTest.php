<?php

use App\Actions\Auth\RegisterUser;
use App\Data\Auth\RegisterUserData;
use App\Models\User;

it('registers a user', function () {
    $user = app(RegisterUser::class)->execute(
        new RegisterUserData(
            name: 'John Doe',
            email: 'john@example.com',
            password: 'secret123',
        ),
    );

    expect($user)
        ->toBeInstanceOf(User::class)
        ->and($user->name)
        ->toBe('John Doe')
        ->and($user->email)
        ->toBe('john@example.com');

    expect(User::where('email', 'john@example.com')->exists())
        ->toBeTrue();
});
