<?php

namespace App\Modules\Tenancy\Actions;

use App\Models\User;
use App\Modules\Tenancy\Enums\MembershipStatus;
use App\Modules\Tenancy\Enums\TenantRole;
use App\Modules\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\DB;

final class CreateTenantAction
{
    public function execute(User $owner, string $name, string $slug): Tenant
    {
        return DB::transaction(function () use ($owner, $name, $slug): Tenant {
            $tenant = Tenant::create([
                'name' => $name,
                'slug' => $slug,
            ]);

            $tenant->memberships()->create([
                'user_id' => $owner->id,
                'role' => TenantRole::Owner,
                'status' => MembershipStatus::Active,
            ]);

            return $tenant;
        });
    }
}
