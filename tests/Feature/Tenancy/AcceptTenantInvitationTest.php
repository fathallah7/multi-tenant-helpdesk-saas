<?php

use App\Models\User;
use App\Modules\Tenancy\Actions\CreateTenantAction;
use App\Modules\Tenancy\Enums\MembershipStatus;
use App\Modules\Tenancy\Enums\TenantRole;
use App\Modules\Tenancy\Models\TenantInvitation;
use App\Modules\Tenancy\Models\TenantMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->owner = User::factory()->create();

    $this->agent = User::factory()->create([
        'email' => 'agent@example.com',
    ]);

    $this->tenant = app(CreateTenantAction::class)->execute(
        owner: $this->owner,
        name: 'Acme Support',
        slug: 'acme-support',
    );

    $this->token = bin2hex(random_bytes(32));

    $this->invitation = TenantInvitation::query()->create([
        'tenant_id' => $this->tenant->id,
        'email' => $this->agent->email,
        'role' => TenantRole::Agent,
        'token_hash' => hash('sha256', $this->token),
        'invited_by' => $this->owner->id,
        'expires_at' => now()->addDays(7),
    ]);
});

test('user can accept a valid invitation', function () {
    Sanctum::actingAs($this->agent);

    $this->postJson('/api/v1/invitations/accept', [
        'token' => $this->token,
    ])
        ->assertOk()
        ->assertJsonPath('data.tenant.id', $this->tenant->id)
        ->assertJsonPath('data.role', 'agent')
        ->assertJsonPath('data.status', 'active');

    $this->assertDatabaseHas('tenant_memberships', [
        'tenant_id' => $this->tenant->id,
        'user_id' => $this->agent->id,
        'role' => TenantRole::Agent->value,
        'status' => MembershipStatus::Active->value,
    ]);

    expect($this->invitation->fresh()->accepted_at)
        ->not->toBeNull();
});

test('invitation cannot be accepted twice', function () {
    Sanctum::actingAs($this->agent);

    $this->postJson('/api/v1/invitations/accept', [
        'token' => $this->token,
    ])->assertOk();

    $this->postJson('/api/v1/invitations/accept', [
        'token' => $this->token,
    ])->assertUnprocessable();

    $this->assertDatabaseCount('tenant_memberships', 2);
});

test('expired invitation cannot be accepted', function () {
    $this->invitation->expires_at = now()->subMinute();
    $this->invitation->save();

    Sanctum::actingAs($this->agent);

    $this->postJson('/api/v1/invitations/accept', [
        'token' => $this->token,
    ])->assertUnprocessable();

    $this->assertDatabaseMissing('tenant_memberships', [
        'tenant_id' => $this->tenant->id,
        'user_id' => $this->agent->id,
    ]);
});

test('revoked invitation cannot be accepted', function () {
    $this->invitation->revoked_at = now();
    $this->invitation->save();

    Sanctum::actingAs($this->agent);

    $this->postJson('/api/v1/invitations/accept', [
        'token' => $this->token,
    ])->assertUnprocessable();

    expect($this->invitation->fresh()->accepted_at)
        ->toBeNull();
});

test('another user cannot accept the invitation', function () {
    $otherUser = User::factory()->create();

    Sanctum::actingAs($otherUser);

    $this->postJson('/api/v1/invitations/accept', [
        'token' => $this->token,
    ])->assertForbidden();

    $this->assertDatabaseMissing('tenant_memberships', [
        'tenant_id' => $this->tenant->id,
        'user_id' => $otherUser->id,
    ]);
});

test('existing member cannot accept another invitation', function () {
    TenantMembership::query()->create([
        'tenant_id' => $this->tenant->id,
        'user_id' => $this->agent->id,
        'role' => TenantRole::Agent,
        'status' => MembershipStatus::Active,
    ]);

    Sanctum::actingAs($this->agent);

    $this->postJson('/api/v1/invitations/accept', [
        'token' => $this->token,
    ])->assertUnprocessable();

    expect($this->invitation->fresh()->accepted_at)
        ->toBeNull();
});

test('guest cannot accept invitation', function () {
    $this->postJson('/api/v1/invitations/accept', [
        'token' => $this->token,
    ])->assertUnauthorized();
});
