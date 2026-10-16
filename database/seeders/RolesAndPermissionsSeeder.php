<?php

namespace Database\Seeders;

use App\Services\Rbac\TeamRoleProvisioner;
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
        app(TeamRoleProvisioner::class)->syncGlobalPermissions();
    }
}