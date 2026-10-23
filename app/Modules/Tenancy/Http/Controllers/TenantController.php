<?php

namespace App\Modules\Tenancy\Http\Controllers;

use App\Modules\Tenancy\Actions\CreateTenantAction;
use App\Modules\Tenancy\Http\Requests\StoreTenantRequest;
use App\Modules\Tenancy\Http\Resources\TenantResource;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class TenantController
{
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
