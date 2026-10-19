<?php

namespace App\Services\Rbac;

use App\Models\User;
use Illuminate\Support\Facades\DB;

final class ProviderMembershipLookup
{
    /** @var array<string, bool> */
    private array $results = [];

    public function exists(
        User $user,
        int|string $providerId,
    ): bool {
        /*
         * Don't cache results inside transactions. A transaction may roll
         * back after a lookup, leaving request-scoped state inconsistent
         * with the database.
         */
        if (DB::transactionLevel() > 0) {
            return $this->queryExists($user, $providerId);
        }

        $key = $this->key($user, $providerId);

        return $this->results[$key] ??=
            $this->queryExists($user, $providerId);
    }

    public function forget(
        User $user,
        int|string $providerId,
    ): void {
        unset($this->results[$this->key($user, $providerId)]);
    }

    private function queryExists(
        User $user,
        int|string $providerId,
    ): bool {
        return $user
            ->providerMemberships()
            ->where('parking_provider_id', $providerId)
            ->exists();
    }

    private function key(
        User $user,
        int|string $providerId,
    ): string {
        return serialize([
            $user->getKey(),
            (string) $providerId,
        ]);
    }
}
