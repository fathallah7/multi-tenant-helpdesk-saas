
<?php

use App\Models\User;
use App\Modules\Tenancy\Enums\MembershipStatus;
use App\Modules\Tenancy\Enums\TenantRole;
use App\Modules\Tenancy\Models\Tenant;
use App\Modules\Tenancy\Models\TenantMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('user can only list tenants they belong to', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $myTenant = Tenant::query()->create([
        'name' => 'My Company',
        'slug' => 'my-company',
    ]);

    $otherTenant = Tenant::query()->create([
        'name' => 'Other Company',
        'slug' => 'other-company',
    ]);

    TenantMembership::query()->create([
        'tenant_id' => $myTenant->id,
        'user_id' => $user->id,
        'role' => TenantRole::Owner,
        'status' => MembershipStatus::Active,
    ]);

    TenantMembership::query()->create([
        'tenant_id' => $otherTenant->id,
        'user_id' => $otherUser->id,
        'role' => TenantRole::Owner,
        'status' => MembershipStatus::Active,
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/tenants');

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $myTenant->id)
        ->assertJsonPath('data.0.role', 'owner')
        ->assertJsonMissing([
            'slug' => 'other-company',
        ]);
});

test('suspended memberships are excluded from tenant listing', function () {
    $user = User::factory()->create();

    $activeTenant = Tenant::query()->create([
        'name' => 'Active Company',
        'slug' => 'active-company',
    ]);

    $suspendedTenant = Tenant::query()->create([
        'name' => 'Suspended Company',
        'slug' => 'suspended-company',
    ]);

    TenantMembership::query()->create([
        'tenant_id' => $activeTenant->id,
        'user_id' => $user->id,
        'role' => TenantRole::Owner,
        'status' => MembershipStatus::Active,
    ]);

    TenantMembership::query()->create([
        'tenant_id' => $suspendedTenant->id,
        'user_id' => $user->id,
        'role' => TenantRole::Agent,
        'status' => MembershipStatus::Suspended,
    ]);

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/tenants')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $activeTenant->id)
        ->assertJsonMissing([
            'slug' => 'suspended-company',
        ]);
});

test('guest cannot list tenants', function () {
    $this->getJson('/api/v1/tenants')
        ->assertUnauthorized();
});
