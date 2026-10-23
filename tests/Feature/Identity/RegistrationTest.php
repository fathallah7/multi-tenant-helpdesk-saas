
<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('registers a user successfully', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Abdo',
        'email' => '  ABDO@EXAMPLE.COM  ',
        'password' => 'SecurePassword123',
        'password_confirmation' => 'SecurePassword123',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.user.name', 'Abdo')
        ->assertJsonPath('data.user.email', 'abdo@example.com')
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonStructure([
            'data' => [
                'user' => ['id', 'name', 'email'],
                'token',
                'token_type',
            ],
        ]);

    $user = User::query()
        ->where('email', 'abdo@example.com')
        ->firstOrFail();

    expect(Hash::check('SecurePassword123', $user->password))
        ->toBeTrue();

    expect($user->password)
        ->not->toBe('SecurePassword123');

    expect($user->tokens()->count())
        ->toBe(1);

    $response->assertJsonMissingPath('data.user.password');
});

test('rejects registration with an existing email', function () {
    User::factory()->create([
        'email' => 'abdo@example.com',
    ]);

    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Another User',
        'email' => 'ABDO@EXAMPLE.COM',
        'password' => 'SecurePassword123',
        'password_confirmation' => 'SecurePassword123',
    ]);

    $response->assertUnprocessable();

    expect(
        User::query()
            ->where('email', 'abdo@example.com')
            ->count()
    )->toBe(1);
});

test('rejects registration with mismatched passwords', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Abdo',
        'email' => 'abdo@example.com',
        'password' => 'SecurePassword123',
        'password_confirmation' => 'DifferentPassword123',
    ]);

    $response->assertUnprocessable();

    expect(User::query()->count())->toBe(0);
});
