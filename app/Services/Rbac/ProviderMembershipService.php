<?php

namespace App\Services\Rbac;

use App\Models\ParkingProvider;
use App\Models\ProviderMembership;
use App\Models\User;
use App\Support\Rbac\Concerns\InteractsWithTeams;
use App\Support\Rbac\Role;
use Illuminate\Support\Facades\DB;

final class ProviderMembershipService
{
    use InteractsWithTeams;

    /**
     * The provider's Spatie team roles must already be provisioned.
     */
    public function assign(
        User $user,
        ParkingProvider $provider,
        Role $role,
    ): ProviderMembership {
        return DB::transaction(function () use ($user, $provider, $role) {
            $membership = ProviderMembership::query()->updateOrCreate(
                [
                    'user_id' => $user->getKey(),
                    'parking_provider_id' => $provider->getKey(),
                ],
                ['role' => $role->value],
            );

            $this->withTeam($provider->getKey(), function () use ($user, $role) {
                $user->unsetRelation('roles');
                $user->unsetRelation('permissions');

                try {
                    $user->syncRoles([$role->value]);
                } finally {
                    $user->unsetRelation('roles');
                    $user->unsetRelation('permissions');
                }
            });

            return $membership;
        });
    }

    /**
     * Remove a membership and revoke the user's roles in that team.
     */
    public function remove(
        User $user,
        ParkingProvider $provider,
    ): bool {
        return DB::transaction(function () use ($user, $provider) {
            $membership = ProviderMembership::query()
                ->where('user_id', $user->getKey())
                ->where('parking_provider_id', $provider->getKey())
                ->first();

            $this->withTeam($provider->getKey(), function () use ($user) {
                $user->unsetRelation('roles');
                $user->unsetRelation('permissions');

                try {
                    $user->syncRoles([]);
                } finally {
                    $user->unsetRelation('roles');
                    $user->unsetRelation('permissions');
                }
            });

            $membership?->delete();

            return $membership !== null;
        });
    }
}