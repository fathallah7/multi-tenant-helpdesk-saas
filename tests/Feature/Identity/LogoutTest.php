<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

test('user can logout and revoke current token', function () {
    $user = User::factory()->create();

    $token = $user->createToken('supporthub-api')
        ->plainTextToken;

    $this
        ->withToken($token)
        ->postJson('/api/v1/auth/logout')
        ->assertOk();

    expect($user->tokens()->count())->toBe(0);

    // Clear the cached user between simulated requests.
    Auth::guard('sanctum')->forgetUser();

    $this
        ->withToken($token)
        ->getJson('/api/v1/auth/me')
        ->assertUnauthorized();
});

test('logout only revokes the current token', function () {
    $user = User::factory()->create();

    $tokenA = $user->createToken('laptop')->plainTextToken;
    $tokenB = $user->createToken('mobile')->plainTextToken;

    $this
        ->withToken($tokenA)
        ->postJson('/api/v1/auth/logout')
        ->assertOk();

    expect($user->tokens()->count())->toBe(1);

    Auth::guard('sanctum')->forgetUser();

    $this
        ->withToken($tokenA)
        ->getJson('/api/v1/auth/me')
        ->assertUnauthorized();

    Auth::guard('sanctum')->forgetUser();

    $this
        ->withToken($tokenB)
        ->getJson('/api/v1/auth/me')
        ->assertOk();
});

test('guest cannot logout', function () {
    $this
        ->postJson('/api/v1/auth/logout')
        ->assertUnauthorized();
});
