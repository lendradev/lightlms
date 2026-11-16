<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Render the friendly error page for browser requests that reach the
     * error views. JSON and Inertia requests keep the default behaviour.
     *
     * @param  array<string, mixed>  $context
     */
    public function render($request, Throwable $e): Response
    {
        if ($this->shouldRenderCustomView($request, $e)) {
            $status = $e instanceof HttpExceptionInterface
                ? $e->getStatusCode()
                : 500;

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
        }

        return parent::render($request, $e);
    }

    private function shouldRenderCustomView(Request $request, Throwable $e): bool
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return false;
        }

        if ($request->header('X-Inertia')) {
            return false;
        }

        return ! app()->environment('testing');
    }
}
