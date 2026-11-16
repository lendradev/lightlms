<?php

namespace App\Http\Middleware;

use App\Support\Locale;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Picks the locale for the request.
 *
 * The order is cookie, then Accept-Language, then the configured default. A
 * locale that is not registered is ignored rather than rejected, so a stale or
 * tampered cookie cannot break the site.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolve($request);

        app()->setLocale($locale);

        return $next($request);
    }

    private function resolve(Request $request): string
    {
        $cookie = $request->cookie(Locale::COOKIE);

        if (is_string($cookie) && Locale::isAvailable($cookie)) {
            return $cookie;
        }

        $header = Locale::fromHeader($request->header('Accept-Language'));

        if ($header !== null) {
            return $header;
        }

        $configured = config('app.locale');

        return is_string($configured) && Locale::isAvailable($configured)
            ? $configured
            : Locale::fallback();
    }
}
