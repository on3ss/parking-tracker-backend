<?php

namespace App\Services\Rbac;

use App\Models\ParkingProvider;
use App\Models\ProviderMembership;
use App\Models\User;
use App\Support\Rbac\Concerns\InteractsWithTeams;
use App\Support\Rbac\Role;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class ProviderMembershipService
{
    use InteractsWithTeams;

    /**
     * Assign a user's role within a provider.
     *
     * The domain membership and Spatie team role are kept in sync.
     */
    public function assign(
        User $user,
        ParkingProvider $provider,
        Role $role,
    ): ProviderMembership {
        return DB::transaction(function () use ($user, $provider, $role): ProviderMembership {
            $membership = ProviderMembership::query()->updateOrCreate(
                [
                    'user_id' => $user->getKey(),
                    'parking_provider_id' => $provider->getKey(),
                ],
                [
                    'role' => $role->value,
                ],
            );

            $this->withTeam($provider->getKey(), function () use ($user, $role): void {
                $user->unsetRelation('roles');
                $user->unsetRelation('permissions');

                $user->syncRoles([$role->value]);

                $user->unsetRelation('roles');
                $user->unsetRelation('permissions');
            });

            return $membership;
        });
    }
}
