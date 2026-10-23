<?php

use App\Models\User;
use App\Modules\Tenancy\Enums\TenantRole;
use App\Modules\Tenancy\Models\Tenant;
use App\Modules\Tenancy\Models\TenantMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('tenant membership connects a user to a tenant', function () {
    $user = User::factory()->create();

    $tenant = Tenant::create([
        'name' => 'Acme Company',
        'slug' => 'acme',
    ]);

    $membership = TenantMembership::create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'role' => 'owner',
        'status' => 'active',
    ]);

    expect($tenant->memberships)->toHaveCount(1);

    expect($membership->tenant->is($tenant))->toBeTrue();

    expect($membership->user->is($user))->toBeTrue();

    expect($user->tenantMemberships)->toHaveCount(1);
});

test('user can belong to multiple tenants with different roles', function () {
    $user = User::factory()->create();

    $tenantA = Tenant::create([
        'name' => 'Company A',
        'slug' => 'company-a',
    ]);

    $tenantB = Tenant::create([
        'name' => 'Company B',
        'slug' => 'company-b',
    ]);

    TenantMembership::create([
        'tenant_id' => $tenantA->id,
        'user_id' => $user->id,
        'role' => 'owner',
        'status' => 'active',
    ]);

    TenantMembership::create([
        'tenant_id' => $tenantB->id,
        'user_id' => $user->id,
        'role' => 'agent',
        'status' => 'active',
    ]);

    expect($user->tenantMemberships()->count())->toBe(2);

    expect(
        $user
            ->tenantMemberships()
            ->where('tenant_id', $tenantA->id)
            ->firstOrFail()->role,
    )->toBe(TenantRole::Owner);

    expect(
        $user
            ->tenantMemberships()
            ->where('tenant_id', $tenantB->id)
            ->firstOrFail()->role,
    )->toBe(TenantRole::Agent);
});
