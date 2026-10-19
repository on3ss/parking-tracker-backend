<?php

namespace Tests\Support;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class PlatformAdminTestHelper
{
    private const ROLE = 'platform-admin';

    private const GUARD = 'web';

    public function grant(User $user): void
    {
        $registrar = app(PermissionRegistrar::class);
        $previousTeamId = $registrar->getPermissionsTeamId();

        try {
            $registrar->setPermissionsTeamId(null);

            $role = Role::query()
                ->where('name', self::ROLE)
                ->where('guard_name', self::GUARD)
                ->whereNull('parking_provider_id')
                ->first();

            if ($role === null) {
                $role = Role::query()->create([
                    'name' => self::ROLE,
                    'guard_name' => self::GUARD,
                    'parking_provider_id' => null,
                ]);
            }

            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');

            $user->assignRole($role);
        } finally {
            $registrar->setPermissionsTeamId($previousTeamId);

            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');
        }
    }
}