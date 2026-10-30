<?php

namespace App\Shared\Http\Exceptions;

use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

final class ApiExceptionRenderer
{
    public function render(
        Throwable $exception,
        Request $request,
    ): ?JsonResponse {
        if (! $request->is('api/*')) {
            return null;
        }

        if ($exception instanceof ValidationException) {
            return ApiResponse::error(
                message: 'Validation failed.',
                status: Response::HTTP_UNPROCESSABLE_ENTITY,
                errors: $exception->errors(),
            );
        }

        if ($exception instanceof AuthenticationException) {
            return ApiResponse::error(
                message: 'Unauthenticated.',
                status: Response::HTTP_UNAUTHORIZED
            );
        }

        if ($exception instanceof AuthorizationException) {
            return ApiResponse::error(
                message: 'Forbidden.',
                status: Response::HTTP_FORBIDDEN,
            );
        }

        if ($exception instanceof HttpExceptionInterface) {
            $status = $exception->getStatusCode();

            $message = match ($status) {
                403 => 'Forbidden.',
                404 => 'Resource not found.',
                405 => 'Method not allowed.',
                429 => 'Too many requests.',
                default => Response::$statusTexts[$status] ??
                    'Request failed.',
            };

            return ApiResponse::error(
                message: $message,
                status: $status,
                headers: $exception->getHeaders(),
            );
        }

        return ApiResponse::error(
            message: 'Internal server error.',
            status: 500,
        );
    }
}
