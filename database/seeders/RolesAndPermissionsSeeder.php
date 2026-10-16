<?php

namespace Database\Seeders;

use App\Services\Rbac\TeamRoleProvisioner;
use App\Support\Rbac\Concerns\InteractsWithTeams;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    use InteractsWithTeams;

    public const GUARD = 'web';

    public function run(): void
    {
        app(TeamRoleProvisioner::class)->syncGlobalPermissions();
    }
}
