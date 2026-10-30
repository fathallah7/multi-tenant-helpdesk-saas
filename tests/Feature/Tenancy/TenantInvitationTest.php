<?php

use App\Models\User;
use App\Modules\Tenancy\Actions\CreateTenantAction;
use App\Modules\Tenancy\Enums\MembershipStatus;
use App\Modules\Tenancy\Enums\TenantRole;
use App\Modules\Tenancy\Models\TenantInvitation;
use App\Modules\Tenancy\Models\TenantMembership;
use App\Modules\Tenancy\Notifications\TenantInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();

    $this->owner = User::factory()->create();

    $this->tenant = app(CreateTenantAction::class)->execute(
        owner: $this->owner,
        name: 'Acme Support',
        slug: 'acme-support',
    );
});

test('owner can invite a user and dispatch notification', function () {
    Sanctum::actingAs($this->owner);

    $response = $this->postJson(
        "/api/v1/tenants/{$this->tenant->id}/invitations",
        ['email' => ' AGENT@EXAMPLE.COM '],
    );

    $response
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.email', 'agent@example.com')
        ->assertJsonPath('data.role', 'agent')
        ->assertJsonMissingPath('data.token')
        ->assertJsonMissingPath('data.token_hash');

    $invitation = TenantInvitation::query()->firstOrFail();

    expect($invitation->token_hash)->toHaveLength(64);
    expect($invitation->role)->toBe(TenantRole::Agent);
    expect($invitation->accepted_at)->toBeNull();

    Notification::assertSentOnDemand(
        TenantInvitationNotification::class,
        function (
            TenantInvitationNotification $notification,
            array $channels,
            object $notifiable,
        ): bool {
            return $notifiable->routes['mail'] === 'agent@example.com'
              && in_array('mail', $channels, true);
        },
    );

    Notification::assertSentOnDemandTimes(
        TenantInvitationNotification::class,
        1,
    );
});

test('agent cannot invite users', function () {
    $agent = User::factory()->create();

    TenantMembership::query()->create([
        'tenant_id' => $this->tenant->id,
        'user_id' => $agent->id,
        'role' => TenantRole::Agent,
        'status' => MembershipStatus::Active,
    ]);

    Sanctum::actingAs($agent);

    $this->postJson(
        "/api/v1/tenants/{$this->tenant->id}/invitations",
        ['email' => 'new@example.com'],
    )->assertForbidden();

    $this->assertDatabaseCount('tenant_invitations', 0);

    Notification::assertNothingSent();
});

test('suspended owner cannot invite users', function () {
    $membership = TenantMembership::query()
        ->where('tenant_id', $this->tenant->id)
        ->where('user_id', $this->owner->id)
        ->firstOrFail();

    $membership->update([
        'status' => MembershipStatus::Suspended,
    ]);

    Sanctum::actingAs($this->owner);

    $this->postJson(
        "/api/v1/tenants/{$this->tenant->id}/invitations",
        ['email' => 'new@example.com'],
    )->assertForbidden();

    $this->assertDatabaseCount('tenant_invitations', 0);
});

test('cannot create duplicate pending invitations', function () {
    Sanctum::actingAs($this->owner);

    $url = "/api/v1/tenants/{$this->tenant->id}/invitations";

    $this->postJson($url, [
        'email' => 'agent@example.com',
    ])->assertCreated();

    $this->postJson($url, [
        'email' => 'AGENT@EXAMPLE.COM',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);

    $this->assertDatabaseCount('tenant_invitations', 1);

    Notification::assertSentOnDemandTimes(
        TenantInvitationNotification::class,
        1,
    );
});

test('cannot invite an existing tenant member', function () {
    Sanctum::actingAs($this->owner);

    $this->postJson(
        "/api/v1/tenants/{$this->tenant->id}/invitations",
        ['email' => $this->owner->email],
    )
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);

    $this->assertDatabaseCount('tenant_invitations', 0);

    Notification::assertNothingSent();
});

test('cannot invite with invalid email', function () {
    Sanctum::actingAs($this->owner);

    $this->postJson(
        "/api/v1/tenants/{$this->tenant->id}/invitations",
        ['email' => 'invalid-email'],
    )
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);

    $this->assertDatabaseCount('tenant_invitations', 0);
});

test('guest cannot invite users', function () {
    $this->postJson(
        "/api/v1/tenants/{$this->tenant->id}/invitations",
        ['email' => 'agent@example.com'],
    )->assertUnauthorized();

    $this->assertDatabaseCount('tenant_invitations', 0);
});
