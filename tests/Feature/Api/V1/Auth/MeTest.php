<?php

use App\Models\User;

it('returns the authenticated user', function () {
    $user = User::factory()->create([
        'name' => 'John',
        'email' => 'john@example.com',
    ]);

    $response = $this
        ->actingAs($user, 'sanctum')
        ->getJson('/api/v1/auth/me');

    $response
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.name', 'John')
        ->assertJsonPath('data.email', 'john@example.com');
});
