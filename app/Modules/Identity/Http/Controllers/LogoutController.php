<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogoutController
{
    public function destroy(Request $request): JsonResponse
    {
        $request->user()
            ->currentAccessToken()
            ->delete();

        return ApiResponse::success(
            message: 'Logged out successfully.',
        );
    }
}
