<?php

namespace App\Services\Rbac;

use App\Models\User;
use App\Support\Rbac\Permission;

final class ProviderAuthorization
{
    public function __construct(private ProviderMembershipLookup $membershipLookup) {}

    public function allowsCurrent(
        User $user,
        Permission $permission,
    ): bool {
        return $this->allows(
            $user,
            getPermissionsTeamId(),
            $permission,
        );
    }

    public function allows(
        User $user,
        int|string|null $providerId,
        Permission $permission,
    ): bool {
        if ($providerId === null) {
            return false;
        }

        $currentTeamId = getPermissionsTeamId();

        if (
            $currentTeamId !== null
            && (string) $currentTeamId !== (string) $providerId
        ) {
            return false;
        }

        if (
            ! $this->membershipLookup->exists($user, $providerId)
        ) {
            return false;
        }

        try {
            setPermissionsTeamId($providerId);

            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');

            return $user->hasPermissionTo(
                $permission->value,
                TeamRoleProvisioner::GUARD,
            );
        } finally {
            setPermissionsTeamId($currentTeamId);

            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');
        }
    }
}
