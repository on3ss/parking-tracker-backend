<?php

use App\Models\User;

it('allows a user to register', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        'device_name' => 'test-device',
    ]);

    $response
        ->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                ],
                'token',
            ],
        ]);

    expect(User::where('email', 'john@example.com')->exists())
        ->toBeTrue();
});
