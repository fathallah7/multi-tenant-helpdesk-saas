
<?php
use App\Models\User;
use App\Modules\Tenancy\Enums\TenantRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test(
    'authenticated user can create a tenant and becomes its owner',
    function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/tenants', [
            'name' => 'Acme Support',
            'slug' => 'acme-support',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Acme Support')
            ->assertJsonPath('data.slug', 'acme-support');

        $tenantId = $response->json('data.id');

        $this->assertDatabaseHas('tenants', [
            'id' => $tenantId,
            'slug' => 'acme-support',
        ]);

        $this->assertDatabaseHas('tenant_memberships', [
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'role' => TenantRole::Owner->value,
        ]);
    },
);

test('guest cannot create a tenant', function () {
    $response = $this->postJson('/api/v1/tenants', [
        'name' => 'Acme Support',
        'slug' => 'acme-support',
    ]);

    $response->assertUnauthorized()->assertJsonPath('success', false);

    $this->assertDatabaseCount('tenants', 0);
    $this->assertDatabaseCount('tenant_memberships', 0);
});

test('user cannot create a tenant with an existing slug', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $this->postJson('/api/v1/tenants', [
        'name' => 'Acme Support',
        'slug' => 'acme-support',
    ])->assertCreated();

    $response = $this->postJson('/api/v1/tenants', [
        'name' => 'Another Company',
        'slug' => 'acme-support',
    ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['slug']);

    $this->assertDatabaseCount('tenants', 1);
    $this->assertDatabaseCount('tenant_memberships', 1);
});
