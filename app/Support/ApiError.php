<?php

namespace App\Support;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * The error shape every API response uses.
 *
 * A client should be able to read one field and know what happened, without
 * parsing prose or guessing from the status code alone.
 */
class ApiError
{
    /**
     * The machine-readable code for a status.
     */
    public static function codeFor(int $status): string
    {
        return match ($status) {
            401 => 'unauthenticated',
            403 => 'forbidden',
            404 => 'not_found',
            405 => 'method_not_allowed',
            419 => 'session_expired',
            422 => 'validation_failed',
            429 => 'rate_limited',
            default => $status >= 500 ? 'server_error' : 'request_failed',
        };
    }

    /**
     * The response body for an exception.
     *
     * @param  array<string, mixed>  $details
     */
    public static function response(Request $request, Throwable $exception, int $status, array $details = []): JsonResponse
    {
        $message = $exception->getMessage();

        if ($status >= 500) {
            // The real reason may name a host, a query or a path. The client
            // gets a generic sentence instead.
            $message = 'The server ran into a problem. Please try again in a moment.';
        }

        return response()->json([
            'error' => [
                'code' => self::codeFor($status),
                'message' => $message === '' ? 'The request could not be completed.' : $message,
                'details' => $details === [] ? new \stdClass : $details,
            ],
        ], $status);
    }

    /**
     * The status and details for an exception.
     *
     * @return array{status: int, details: array<string, mixed>}
     */
    public static function describe(Throwable $exception): array
    {
        if ($exception instanceof ValidationException) {
            return ['status' => 422, 'details' => $exception->errors()];
        }

        if ($exception instanceof AuthenticationException) {
            return ['status' => 401, 'details' => []];
        }

        if ($exception instanceof HttpExceptionInterface) {
            return ['status' => $exception->getStatusCode(), 'details' => []];
        }

        return ['status' => 500, 'details' => []];
    }
}
