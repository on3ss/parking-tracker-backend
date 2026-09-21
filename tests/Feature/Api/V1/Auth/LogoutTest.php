<?php

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

it('logs out the current device', function () {
    $user = User::factory()->create();

    $token = $user->createToken('test-device');

    $response = $this
        ->withToken($token->plainTextToken)
        ->postJson('/api/v1/auth/logout');

    $response
        ->assertOk()
        ->assertJson([
            'message' => 'Logged out successfully.',
        ]);

    expect(
        PersonalAccessToken::find($token->accessToken->id)
    )->toBeNull();
});