<?php

namespace App\Modules\Tenancy\Actions;

use App\Models\User;
use App\Modules\Tenancy\Enums\TenantRole;
use App\Modules\Tenancy\Models\Tenant;
use App\Modules\Tenancy\Models\TenantInvitation;
use App\Modules\Tenancy\Models\TenantMembership;
use App\Modules\Tenancy\Notifications\TenantInvitationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

final class InviteTenantMemberAction
{
    public function execute(
        Tenant $tenant,
        User $inviter,
        string $email,
    ): TenantInvitation {
        $email = strtolower(trim($email));

        return DB::transaction(function () use (
            $tenant,
            $inviter,
            $email,
        ): TenantInvitation {

            // Serialize invitation creation for this tenant.
            Tenant::query()
                ->whereKey($tenant->id)
                ->lockForUpdate()
                ->firstOrFail();

            $alreadyMember = TenantMembership::query()
                ->where('tenant_id', $tenant->id)
                ->whereHas(
                    'user',
                    fn ($query) => $query->where('email', $email)
                )
                ->exists();

            if ($alreadyMember) {
                throw ValidationException::withMessages([
                    'email' => 'This user is already a tenant member.',
                ]);
            }

            $pendingInvitation = TenantInvitation::query()
                ->where('tenant_id', $tenant->id)
                ->where('email', $email)
                ->whereNull('accepted_at')
                ->whereNull('revoked_at')
                ->where('expires_at', '>', now())
                ->exists();

            if ($pendingInvitation) {
                TenantInvitation::query()->where('email', $email)->delete();
            }

            $token = bin2hex(random_bytes(32));

            $invitation = TenantInvitation::query()->create([
                'tenant_id' => $tenant->id,
                'email' => $email,
                'role' => TenantRole::Agent,
                'token_hash' => hash('sha256', $token),
                'invited_by' => $inviter->id,
                'expires_at' => now()->addDays(7),
            ]);

            Notification::route('mail', $email)->notify(
                new TenantInvitationNotification(
                    invitationId: $invitation->id,
                    tenantName: $tenant->name,
                    token: $token,
                )
            );

            return $invitation;
        });
    }
}
