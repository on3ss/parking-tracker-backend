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
     * Idempotent.
     */
    public function syncGlobalPermissions(): void
    {
        $existing = Permission::query()
            ->where('guard_name', self::GUARD)
            ->pluck('name')
            ->all();

        $missing = array_values(array_diff(PermissionEnum::values(), $existing));

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

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Ensure every configured role exists for the given team
     * and is attached to its permissions. Idempotent.
     */
    public function syncTeamRoles(int|string $teamId): void
    {
        $this->withTeam($teamId, function () use ($teamId): void {
            foreach (RoleEnum::cases() as $roleEnum) {
                /** @var Role $role */
                $role = Role::query()->firstOrCreate([
                    'name' => $roleEnum->value,
                    'guard_name' => self::GUARD,
                    config('permission.column_names.team_foreign_key') => $teamId,
                ]);

                $role->syncPermissions($roleEnum->permissionNames());
            }
        });
    }


    /**
     * Provision all RBAC configuration required by a provider.
     *
     * Global permissions are synchronized once, then each provider's
     * team-scoped roles and grants are synchronized.
     *
     * @param iterable<int|string> $providerIds
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
