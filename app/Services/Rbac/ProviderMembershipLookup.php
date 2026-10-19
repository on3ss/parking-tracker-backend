<?php

namespace App\Services\Rbac;

use App\Models\User;
use Illuminate\Support\Facades\DB;

final class ProviderMembershipLookup
{
    /** @var array<string, list<int|string>> */
    private array $providerIds = [];

    /**
     * Return provider IDs for which this user has a membership.
     *
     * Results are request-scoped through the container binding.
     * Do not cache uncommitted transaction state.
     *
     * @return list<int|string>
     */
    public function providerIds(User $user): array
    {
        if (DB::transactionLevel() > 0) {
            return $this->queryProviderIds($user);
        }

        $key = $this->key($user);

        return $this->providerIds[$key] ??=
            $this->queryProviderIds($user);
    }

    public function exists(
        User $user,
        int|string $providerId,
    ): bool {
        foreach ($this->providerIds($user) as $id) {
            if ((string) $id === (string) $providerId) {
                return true;
            }
        }

        return false;
    }

    public function hasAny(User $user): bool
    {
        return $this->providerIds($user) !== [];
    }

    /**
     * Invalidate all membership data for this user.
     */
    public function forget(User $user): void
    {
        unset($this->providerIds[$this->key($user)]);
    }

    /**
     * @return list<int|string>
     */
    private function queryProviderIds(User $user): array
    {
        return $user->providerMemberships()
            ->pluck('parking_provider_id')
            ->all();
    }

    private function key(User $user): string
    {
        return (string) $user->getKey();
    }
}
