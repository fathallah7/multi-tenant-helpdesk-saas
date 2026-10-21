<?php

namespace App\Shared\Http\Responses;

use Illuminate\Http\JsonResponse;

final class ApiResponse
{
    public static function success(
        mixed $data = null,
        string $message = 'Success.',
        int $status = 200,
    ): JsonResponse {
        return response()->json(
            [
                'success' => true,
                'message' => $message,
                'data' => $data,
                'errors' => null,
            ],
            $status,
        );
    }

    public static function error(
        string $message = 'Request failed.',
        int $status = 400,
        ?array $errors = null,
        array $headers = [],
    ): JsonResponse {
        return response()->json(
            [
                'success' => false,
                'message' => $message,
                'data' => null,
                'errors' => $errors,
            ],
            $status,
            $headers,
        );
    }
}
