<?php

namespace Database\Seeders;

use App\Support\Rbac\Concerns\InteractsWithTeams;
use App\Support\Rbac\Permission as PermissionEnum;
use App\Support\Rbac\Role as RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    use InteractsWithTeams;

    public const GUARD = 'web';

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->syncPermissions();
    }

    /**
     * Permissions are global (not team-scoped) in spatie/laravel-permission.
     */
    private function syncPermissions(): void
    {
        $existing = Permission::query()
            ->where('guard_name', self::GUARD)
            ->pluck('name')
            ->all();

        $missing = array_diff(PermissionEnum::values(), $existing);

        if ($missing === []) {
            return;
        }

        $now = now();

        Permission::query()->insert(
            array_map(
                static fn(string $name): array => [
                    'name' => $name,
                    'guard_name' => self::GUARD,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                array_values($missing),
            )
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Idempotently create every configured role for the given team
     * and attach its permissions.
     */
    public static function syncTeamRoles(int|string|null $teamId): void
    {
        (new self)->withTeam($teamId, function () use ($teamId): void {
            foreach (RoleEnum::cases() as $roleEnum) {
                /** @var Role $role */
                $role = Role::query()->firstOrCreate(
                    [
                        'name' => $roleEnum->value,
                        'guard_name' => self::GUARD,
                        'parking_provider_id' => $teamId,
                    ],
                );

                $role->syncPermissions($roleEnum->permissionNames());
            }
        });
    }
}