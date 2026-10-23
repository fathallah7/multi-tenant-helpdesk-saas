<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Modules\Identity\Actions\RegisterUserAction;
use App\Modules\Identity\Http\Requests\RegisterRequest;
use App\Modules\Identity\Http\Resources\UserResource;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class RegisterController
{
    public function store(
        RegisterRequest $request,
        RegisterUserAction $action,
    ): JsonResponse {
        $data = $request->validated();

        $user = $action->execute(
            name: $data['name'],
            email: $data['email'],
            password: $data['password'],
        );

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
            message: 'User registered successfully.',
            status: Response::HTTP_CREATED,
        );
    }
}
