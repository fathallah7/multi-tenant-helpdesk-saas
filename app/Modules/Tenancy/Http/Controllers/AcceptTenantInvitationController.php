<?php

namespace App\Modules\Tenancy\Http\Controllers;

use App\Modules\Tenancy\Actions\AcceptTenantInvitationAction;
use App\Modules\Tenancy\Http\Requests\AcceptTenantInvitationRequest;
use App\Modules\Tenancy\Http\Resources\TenantResource;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class AcceptTenantInvitationController
{
    public function store(
        AcceptTenantInvitationRequest $request,
        AcceptTenantInvitationAction $action,
    ): JsonResponse {
        $data = $request->validated();

        $membership = $action->execute(
            user: $request->user(),
            token: $data['token'],
        );

        return ApiResponse::success(
            data: [
                'tenant' => (new TenantResource(
                    $membership->tenant,
                ))->resolve($request),
                'role' => $membership->role->value,
                'status' => $membership->status->value,
            ],
            message: 'Invitation accepted successfully.',
        );
    }
}
