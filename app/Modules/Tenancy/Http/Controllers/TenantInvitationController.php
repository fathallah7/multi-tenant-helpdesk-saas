<?php

namespace App\Modules\Tenancy\Http\Controllers;

use App\Modules\Tenancy\Actions\InviteTenantMemberAction;
use App\Modules\Tenancy\Http\Requests\StoreTenantInvitationRequest;
use App\Modules\Tenancy\Models\Tenant;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class TenantInvitationController
{
    public function store(
        StoreTenantInvitationRequest $request,
        Tenant $tenant,
        InviteTenantMemberAction $action,
    ): JsonResponse {
        Gate::authorize('manageMembers', $tenant);

        $data = $request->validated();

        $invitation = $action->execute(
            tenant: $tenant,
            inviter: $request->user(),
            email: $data['email'],
        );

        return ApiResponse::success(
            data: [
                'id' => $invitation->id,
                'email' => $invitation->email,
                'role' => $invitation->role->value,
                'expires_at' => $invitation->expires_at->toIso8601String(),
            ],
            message: 'Invitation created and email queued.',
            status: Response::HTTP_CREATED,
        );
    }
}
