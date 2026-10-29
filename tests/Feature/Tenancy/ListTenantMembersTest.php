
<?php

use App\Models\User;
use App\Modules\Tenancy\Actions\CreateTenantAction;
use App\Modules\Tenancy\Enums\MembershipStatus;
use App\Modules\Tenancy\Enums\TenantRole;
use App\Modules\Tenancy\Models\TenantMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('owner can list members of their tenant', function () {
    $owner = User::factory()->create();
    $agent = User::factory()->create();
    $otherOwner = User::factory()->create();

    $action = app(CreateTenantAction::class);

    $tenant = $action->execute(
        owner: $owner,
        name: 'Acme',
        slug: 'acme',
    );

    $action->execute(
        owner: $otherOwner,
        name: 'Other Company',
        slug: 'other-company',
    );

    TenantMembership::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $agent->id,
        'role' => TenantRole::Agent,
        'status' => MembershipStatus::Active,
    ]);

    Sanctum::actingAs($owner);

    $this->getJson("/api/v1/tenants/{$tenant->id}/members")
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.user.id', $owner->id)
        ->assertJsonPath('data.1.user.id', $agent->id)
        ->assertJsonPath('data.1.role', 'agent')
        ->assertJsonMissing([
            'email' => $otherOwner->email,
        ]);
});

test('active agent can list tenant members', function () {
    $owner = User::factory()->create();
    $agent = User::factory()->create();

    $tenant = app(CreateTenantAction::class)->execute(
        owner: $owner,
        name: 'Acme',
        slug: 'acme',
    );

    TenantMembership::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $agent->id,
        'role' => TenantRole::Agent,
        'status' => MembershipStatus::Active,
    ]);

    Sanctum::actingAs($agent);

    $this->getJson("/api/v1/tenants/{$tenant->id}/members")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('outsider cannot list tenant members', function () {
    $owner = User::factory()->create();
    $outsider = User::factory()->create();

    $tenant = app(CreateTenantAction::class)->execute(
        owner: $owner,
        name: 'Acme',
        slug: 'acme',
    );

    Sanctum::actingAs($outsider);

    $this->getJson("/api/v1/tenants/{$tenant->id}/members")
        ->assertForbidden();
});

test('suspended member cannot list tenant members', function () {
    $owner = User::factory()->create();
    $agent = User::factory()->create();

    $tenant = app(CreateTenantAction::class)->execute(
        owner: $owner,
        name: 'Acme',
        slug: 'acme',
    );

    TenantMembership::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $agent->id,
        'role' => TenantRole::Agent,
        'status' => MembershipStatus::Suspended,
    ]);

    Sanctum::actingAs($agent);

    $this->getJson("/api/v1/tenants/{$tenant->id}/members")
        ->assertForbidden();
});

test('guest cannot list tenant members', function () {
    $owner = User::factory()->create();

    $tenant = app(CreateTenantAction::class)->execute(
        owner: $owner,
        name: 'Acme',
        slug: 'acme',
    );

    $this->getJson("/api/v1/tenants/{$tenant->id}/members")
        ->assertUnauthorized();
});
