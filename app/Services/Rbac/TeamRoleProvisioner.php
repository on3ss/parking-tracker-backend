<?php

namespace App\Services\Rbac;

use App\Support\Rbac\Concerns\InteractsWithTeams;
use App\Support\Rbac\Permission as PermissionEnum;
use App\Support\Rbac\Role as RoleEnum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class TeamRoleProvisioner
{
    use InteractsWithTeams;

    public const GUARD = 'web';

    /**
     * Ensure every configured permission exists globally.
     * Idempotent; invalidate Spatie's cache only when permissions are added.
     */
    public function syncGlobalPermissions(): void
    {
        $existing = Permission::query()
            ->where('guard_name', self::GUARD)
            ->pluck('name')
            ->all();

        $missing = array_values(
            array_diff(PermissionEnum::values(), $existing),
        );

        if ($missing !== []) {
            $now = now();

            Permission::query()->insert(array_map(
                static fn(string $name): array => [
                    'name' => $name,
                    'guard_name' => self::GUARD,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                $missing,
            ));
        }

        // Must also run when no permissions were inserted.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Ensure every configured role exists for the given team and has
     * exactly its declared permissions. Avoid pivot writes when unchanged.
     */
    public function syncTeamRoles(int|string $teamId): void
    {
        // Role provisioning depends on globally registered permissions.
        $this->syncGlobalPermissions();

        $this->withTeam($teamId, function () use ($teamId): void {
            $teamKey = config('permission.column_names.team_foreign_key');

            foreach (RoleEnum::cases() as $roleEnum) {
                $role = Role::query()->firstOrCreate([
                    'name' => $roleEnum->value,
                    'guard_name' => self::GUARD,
                    $teamKey => $teamId,
                ]);

                $permissionNames = $roleEnum->permissionNames();

                $permissions = Permission::query()
                    ->where('guard_name', self::GUARD)
                    ->whereIn('name', $permissionNames)
                    ->get();

                $resolvedNames = $permissions
                    ->pluck('name')
                    ->all();

                $missing = array_values(
                    array_diff($permissionNames, $resolvedNames),
                );

                if ($missing !== []) {
                    throw new \RuntimeException(
                        'Missing permissions while provisioning role '
                        . $roleEnum->value . ': '
                        . implode(', ', $missing),
                    );
                }

                $desiredPermissionIds = $permissions
                    ->modelKeys();

                $currentPermissionIds = $role->permissions()
                    ->pluck('permissions.id')
                    ->all();

                sort($desiredPermissionIds);
                sort($currentPermissionIds);

                if ($currentPermissionIds !== $desiredPermissionIds) {
                    $role->syncPermissions($permissions);
                }
            }
        });
    }

    /**
     * Provision all RBAC configuration required by a provider.
     *
     * Global permissions are synchronized once, then each provider's
     * team-scoped roles and grants are synchronized.
     *
     * @param  iterable<int|string>  $providerIds
     */
    public function provisionProviders(iterable $providerIds): void
    {
        $providerIds = is_array($providerIds)
            ? array_values(array_unique($providerIds))
            : iterator_to_array(
                (function () use ($providerIds) {
                    foreach ($providerIds as $providerId) {
                        yield (string) $providerId => $providerId;
                    }
                })(),
                false,
            );

        $this->syncGlobalPermissions();

        foreach ($providerIds as $providerId) {
            $this->syncTeamRoles($providerId);
        }
    }

    /**
     * Provision a single provider.
     */
    public function provisionProvider(int|string $providerId): void
    {
        $this->provisionProviders([$providerId]);
    }
}
