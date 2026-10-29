
<?php

use App\Models\User;
use App\Modules\Tenancy\Actions\CreateTenantAction;
use App\Modules\Tenancy\Enums\MembershipStatus;
use App\Modules\Tenancy\Enums\TenantRole;
use App\Modules\Tenancy\Models\TenantMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('tenant owner can view their tenant', function () {
    $owner = User::factory()->create();

    $tenant = app(CreateTenantAction::class)->execute(
        owner: $owner,
        name: 'Acme Support',
        slug: 'acme-support',
    );

    Sanctum::actingAs($owner);

    $this->getJson("/api/v1/tenants/{$tenant->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $tenant->id);
});

test('active agent can view their tenant', function () {
    $owner = User::factory()->create();
    $agent = User::factory()->create();

    $tenant = app(CreateTenantAction::class)->execute(
        owner: $owner,
        name: 'Acme Support',
        slug: 'acme-support',
    );

    TenantMembership::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $agent->id,
        'role' => TenantRole::Agent->value,
        'status' => MembershipStatus::Active->value,
    ]);

    Sanctum::actingAs($agent);

    $this->getJson("/api/v1/tenants/{$tenant->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $tenant->id);
});

test('user cannot view another tenant', function () {
    $owner = User::factory()->create();
    $outsider = User::factory()->create();

    $tenant = app(CreateTenantAction::class)->execute(
        owner: $owner,
        name: 'Private Company',
        slug: 'private-company',
    );

    Sanctum::actingAs($outsider);

    $this->getJson("/api/v1/tenants/{$tenant->id}")
        ->assertForbidden()
        ->assertJsonPath('success', false);
});

        test('inactive member cannot view their tenant', function () {
            $owner = User::factory()->create();
            $agent = User::factory()->create();

            $tenant = app(CreateTenantAction::class)->execute(
                owner: $owner,
                name: 'Acme Support',
                slug: 'acme-support',
            );

            TenantMembership::query()->create([
                'tenant_id' => $tenant->id,
                'user_id' => $agent->id,
                'role' => TenantRole::Agent->value,
                'status' => MembershipStatus::Suspended->value,
            ]);

            Sanctum::actingAs($agent);

            $this->getJson("/api/v1/tenants/{$tenant->id}")
                ->assertForbidden();
        });

test('guest cannot view a tenant', function () {
    $owner = User::factory()->create();

    $tenant = app(CreateTenantAction::class)->execute(
        owner: $owner,
        name: 'Acme Support',
        slug: 'acme-support',
    );

    $this->getJson("/api/v1/tenants/{$tenant->id}")
        ->assertUnauthorized();
});
