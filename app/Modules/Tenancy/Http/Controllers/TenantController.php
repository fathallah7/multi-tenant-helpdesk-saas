<?php

namespace App\Modules\Tenancy\Http\Controllers;

use App\Modules\Tenancy\Actions\CreateTenantAction;
use App\Modules\Tenancy\Http\Requests\StoreTenantRequest;
use App\Modules\Tenancy\Http\Resources\TenantResource;
use App\Modules\Tenancy\Models\Tenant;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class TenantController
{
    public function index(Request $request): JsonResponse
    {
        $memberships = $request->user()
            ->tenantMemberships()
            ->with('tenant')
            ->get();

        $tenants = $memberships->map(
            fn ($membership): array => [
                ...(new TenantResource($membership->tenant))
                    ->resolve($request),

                'role' => $membership->role->value,
            ],
        );

        return ApiResponse::success(
            data: $tenants->values()->all(),
            message: 'Tenants retrieved successfully.',
        );
    }

    public function show(Tenant $tenant): JsonResponse
    {
        Gate::authorize('view', $tenant);

        return ApiResponse::success(
            data: new TenantResource($tenant)->resolve(),
            message: 'Tenant retrieved successfully.',
        );
    }

    public function store(
        StoreTenantRequest $request,
        CreateTenantAction $action,
    ): JsonResponse {
        $data = $request->validated();

        $tenant = $action->execute(
            owner: $request->user(),
            name: $data['name'],
            slug: $data['slug'],
        );

        return ApiResponse::success(
            data: new TenantResource($tenant)->resolve(),
            message: 'Tenant created successfully.',
            status: Response::HTTP_CREATED,
        );
    }
}
