<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Modules\Identity\Http\Resources\UserResource;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController
{
    public function show(Request $request): JsonResponse
    {
        return ApiResponse::success(
            data: (new UserResource($request->user()))
                ->resolve($request),
            message: 'Authenticated user retrieved successfully.',
        );
    }
}
