<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Modules\Identity\Actions\LoginUserAction;
use App\Modules\Identity\Http\Requests\LoginRequest;
use App\Modules\Identity\Http\Resources\UserResource;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;

class LoginController
{
    public function store(
        LoginRequest $request,
        LoginUserAction $action,
    ): JsonResponse {
        $data = $request->validated();

        $user = $action->execute(
            email: $data['email'],
            password: $data['password'],
        );

        if ($user === null) {
            throw new AuthenticationException(
                'Invalid credentials.'
            );
        }

        $token = $user->createToken(
            'supporthub-api',
            ['*'],
            now()->addDays(30),
        );

        return ApiResponse::success(
            data: [
                'user' => new UserResource($user)->resolve(),
                'token' => $token->plainTextToken,
                'token_type' => 'Bearer',
            ],
            message: 'Logged in successfully.',
        );
    }
}
