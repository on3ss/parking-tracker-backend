<?php

namespace App\Services\Rbac;

use App\Models\User;

final class ProviderMembershipLookup
{
    /** @var array<string, bool> */
    private array $results = [];

    public function exists(
        User $user,
        int|string $providerId,
    ): bool {
        $key = $user->getKey() . ':' . $providerId;

        return $this->results[$key] ??= $user
            ->providerMemberships()
            ->where('parking_provider_id', $providerId)
            ->exists();
    }

    public function forget(
        User $user,
        int|string $providerId,
    ): void {
        unset($this->results[$user->getKey() . ':' . $providerId]);
    }
}