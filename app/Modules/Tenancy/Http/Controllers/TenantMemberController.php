<?php

namespace App\Modules\Tenancy\Http\Controllers;

use App\Modules\Tenancy\Http\Resources\TenantMemberResource;
use App\Modules\Tenancy\Models\Tenant;
use App\Modules\Tenancy\Models\TenantMembership;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class TenantMemberController
{
    public function index(Tenant $tenant): JsonResponse
    {
        Gate::authorize('viewMembers', $tenant);

        $memberships = TenantMembership::query()
            ->where('tenant_id', $tenant->id)
            ->with('user:id,name,email')
            ->orderBy('id')
            ->get();

        return ApiResponse::success(
            data: TenantMemberResource::collection(
                $memberships
            )->resolve(),
            message: 'Tenant members retrieved successfully.',
        );
    }
}
