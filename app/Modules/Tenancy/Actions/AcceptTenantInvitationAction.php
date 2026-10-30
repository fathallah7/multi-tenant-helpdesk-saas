<?php

namespace App\Modules\Tenancy\Actions;

use App\Models\User;
use App\Modules\Tenancy\Enums\MembershipStatus;
use App\Modules\Tenancy\Models\TenantInvitation;
use App\Modules\Tenancy\Models\TenantMembership;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AcceptTenantInvitationAction
{
    public function execute(
        User $user,
        string $token,
    ): TenantMembership {
        return DB::transaction(function () use (
            $user,
            $token,
        ): TenantMembership {

            $invitation = TenantInvitation::query()
                ->where('token_hash', hash('sha256', $token))
                ->lockForUpdate()
                ->first();

            if (
                $invitation === null ||
                $invitation->accepted_at !== null ||
                $invitation->revoked_at !== null ||
                ! $invitation->expires_at->isFuture()
            ) {
                throw ValidationException::withMessages([
                    'token' => 'Invalid or expired invitation.',
                ]);
            }

            if (
                strtolower(trim($user->email)) !==
                $invitation->email
            ) {
                throw new AuthorizationException(
                    'This invitation belongs to another account.'
                );
            }

            $alreadyMember = TenantMembership::query()
                ->where('tenant_id', $invitation->tenant_id)
                ->where('user_id', $user->id)
                ->exists();

            if ($alreadyMember) {
                throw ValidationException::withMessages([
                    'token' => 'You are already a tenant member.',
                ]);
            }

            $membership = TenantMembership::query()->create([
                'tenant_id' => $invitation->tenant_id,
                'user_id' => $user->id,
                'role' => $invitation->role,
                'status' => MembershipStatus::Active,
            ]);

            $invitation->accepted_at = now();
            $invitation->save();

            return $membership->load('tenant');
        });
    }
}
