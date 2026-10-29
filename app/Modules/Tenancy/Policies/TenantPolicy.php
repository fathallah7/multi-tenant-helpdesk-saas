<?php

namespace App\Modules\Tenancy\Policies;

use App\Models\User;
use App\Modules\Tenancy\Enums\MembershipStatus;
use App\Modules\Tenancy\Enums\TenantRole;
use App\Modules\Tenancy\Models\Tenant;
use App\Modules\Tenancy\Models\TenantMembership;

final class TenantPolicy
{
    public function view(User $user, Tenant $tenant): bool
    {
        return TenantMembership::query()
            ->where('user_id', $user->id)
            ->where('tenant_id', $tenant->id)
            ->where('status', MembershipStatus::Active)
            ->exists();
    }

    public function viewMembers(User $user, Tenant $tenant): bool
    {
        return $this->view($user, $tenant);
    }

    public function manageMember(User $user, Tenant $tenant): bool
    {
        return TenantMembership::query()
            ->where('user_id', $user->id)
            ->where('tenant_id', $tenant->id)
            ->where('role', TenantRole::Owner)
            ->where('status', MembershipStatus::Active)
            ->exists();
    }
}
