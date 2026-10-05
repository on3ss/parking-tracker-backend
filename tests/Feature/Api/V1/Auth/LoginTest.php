<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('allows a user to login', function () {
    $user = User::factory()->create([
        'email' => 'john@example.com',
        'password' => Hash::make('secret123'),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'john@example.com',
        'password' => 'secret123',
        'device_name' => 'test-device',
    ]);

    $response
        ->assertOk()
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

    expect($user->tokens()->count())->toBe(1);
});

it('rejects invalid credentials', function () {
    User::factory()->create([
        'email' => 'john@example.com',
        'password' => Hash::make(
            'secret123',
        ),
    ]);

    $this
        ->postJson('/api/v1/auth/login', [
            'email' => 'john@example.com',
            'password' => 'wrong-password',
            'device_name' => 'test-device',
        ])
        ->assertUnauthorized();
});