<?php

namespace App\Services\Rbac;

use App\Models\User;
use App\Support\Rbac\PlatformRole;

final class PlatformAuthorization
{
    public function isAdmin(User $user): bool
    {
        $previousTeamId = getPermissionsTeamId();

        try {
            setPermissionsTeamId(null);

            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');

            return $user->hasRole(
                PlatformRole::Admin->value,
                TeamRoleProvisioner::GUARD,
            );
        } finally {
            setPermissionsTeamId($previousTeamId);

            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');
        }
    }
}