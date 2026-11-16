<?php

use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeaders;
use App\Support\ApiError;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware(['web', 'installed'])->group(
                base_path('routes/admin.php'),
            );
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            SecurityHeaders::class,
        ]);

        $middleware->api(append: [
            EnsureInstalled::class,
        ]);

        $middleware->alias([
            'installed' => EnsureInstalled::class,
            'role' => EnsureUserHasRole::class,
        ]);

        $middleware->redirectGuestsTo(fn (Request $request) => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // The API answers every failure with the same body, so a client can
        // read one field instead of parsing prose.
        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $described = ApiError::describe($e);

            return ApiError::response($request, $e, $described['status'], $described['details']);
        });

        // Browser requests that reach an HTTP error get the themed error page
        // instead of Laravel's default one, which ignores the design tokens.
        // Validation and authentication errors keep their own handling: the
        // first needs the session errors, the second needs the redirect.
        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*') || $request->header('X-Inertia')) {
                return null;
            }

            if ($e instanceof ValidationException || $e instanceof AuthenticationException) {
                return null;
            }

            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

            $title = match (true) {
                $status === 404 => 'Page not found',
                $status === 403 => 'Access denied',
                $status === 419 => 'Session expired',
                $status >= 500 => 'Something went wrong',
                default => 'Request could not be completed',
            };

            return response()->view('errors.error', [
                'title' => $title,
                'message' => $status >= 500
                    ? 'The server ran into a problem. Please try again in a moment.'
                    : $e->getMessage(),
            ], $status);
        });
    })->create();
