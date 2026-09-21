<?php

namespace App\Actions\Auth;

use App\Models\User;

final class LogoutUser
{
    public function execute(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }
}
