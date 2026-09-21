<?php

namespace App\Actions\Auth;

use App\Models\User;

final class GetAuthenticatedUser
{
    public function execute(User $user): User
    {
        return $user;
    }
}
