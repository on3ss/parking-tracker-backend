<?php

namespace App\Actions\Auth;

use App\Data\Auth\LoginData;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class AuthenticateUser
{
    public function execute(LoginData $data): array
    {
        $user = User::query()
            ->where('email', $data->email)
            ->first();

        if (
            !$user ||
            !Hash::check($data->password, $user->password)
        ) {
            throw ValidationException::withMessages([
                'email' => 'The provided credentials are incorrect.',
            ]);
        }

        $token = $user
            ->createToken($data->deviceName)
            ->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}
