
<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can retrieve their profile', function () {
    $user = User::factory()->create();

    $token = $user->createToken('supporthub-api')
        ->plainTextToken;

    $response = $this
        ->withToken($token)
        ->getJson('/api/v1/auth/me');

    $response
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.email', $user->email)
        ->assertJsonMissingPath('data.password');
});

test('guest cannot access current user endpoint', function () {
    $response = $this->getJson('/api/v1/auth/me');

    $response->assertUnauthorized();
});

test('invalid token cannot access current user endpoint', function () {
    $response = $this
        ->withToken('invalid-token')
        ->getJson('/api/v1/auth/me');

    $response->assertUnauthorized();
});
