<?php

namespace App\Policies;

use App\Models\ProviderMembership;
use App\Models\User;
use App\Services\Rbac\ProviderAuthorization;
use App\Support\Rbac\Permission;

final class ProviderMembershipPolicy
{
    public function __construct(
        private readonly ProviderAuthorization $authorization,
    ) {
    }

    public function viewAny(User $user): bool
    {
        return $this->authorization->allowsCurrent(
            $user,
            Permission::MembershipView,
        );
    }

    public function view(
        User $user,
        ProviderMembership $membership,
    ): bool {
        return $this->authorization->allows(
            $user,
            $membership->parking_provider_id,
            Permission::MembershipView,
        );
    }

    public function create(User $user): bool
    {
        return $this->authorization->allowsCurrent(
            $user,
            Permission::MembershipInvite,
        );
    }

    public function update(
        User $user,
        ProviderMembership $membership,
    ): bool {
        return $this->authorization->allows(
            $user,
            $membership->parking_provider_id,
            Permission::MembershipUpdate,
        );
    }

    public function delete(
        User $user,
        ProviderMembership $membership,
    ): bool {
        return $this->authorization->allows(
            $user,
            $membership->parking_provider_id,
            Permission::MembershipRemove,
        );
    }
}