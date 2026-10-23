<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('user can login with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'abdo@example.com',
        'password' => Hash::make('SecurePassword123'),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => '  ABDO@EXAMPLE.COM  ',
        'password' => 'SecurePassword123',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.user.id', $user->id)
        ->assertJsonPath('data.user.email', 'abdo@example.com')
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonStructure([
            'data' => [
                'user' => ['id', 'name', 'email'],
                'token',
                'token_type',
            ],
        ]);

    expect($user->tokens()->count())->toBe(1);

    $response->assertJsonMissingPath('data.user.password');
});

test('user cannot login with wrong password', function () {
    $user = User::factory()->create([
        'email' => 'abdo@example.com',
        'password' => Hash::make('CorrectPassword123'),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'abdo@example.com',
        'password' => 'WrongPassword123',
    ]);

    $response->assertUnauthorized();

    expect($user->tokens()->count())->toBe(0);
});

test('user cannot login with non-existing email', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'unknown@example.com',
        'password' => 'SecurePassword123',
    ]);

    $response->assertUnauthorized();

    expect(User::query()->count())->toBe(0);
});

test('login requires email and password', function () {
    $response = $this->postJson('/api/v1/auth/login', []);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'email',
            'password',
        ]);
});
