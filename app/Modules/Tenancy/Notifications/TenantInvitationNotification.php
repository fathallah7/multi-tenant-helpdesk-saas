<?php

namespace App\Modules\Tenancy\Notifications;

use App\Modules\Tenancy\Models\TenantInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TenantInvitationNotification extends Notification implements ShouldQueue, ShouldBeEncrypted
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private readonly int $invitationId,
        private readonly string $tenantName,
        private readonly string $token,
    ) {
        $this->afterCommit();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function shouldSend(
        object $notifiable,
        string $channel,
    ): bool {
        return TenantInvitation::query()
            ->whereKey($this->invitationId)
            ->whereNull('accepted_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->exists();
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {

        $url = rtrim(
            config('supporthub.frontend_url'),
            '/'
        ).'/invitations/accept?token='
            .urlencode($this->token);

        return (new MailMessage)
            ->subject("Invitation to join {$this->tenantName}")
            ->line("You've been invited to join {$this->tenantName} on SupportHub.")
            ->action('Accept Invitation', $url)
            ->line('This invitation expires in 7 days.');
    }
}
