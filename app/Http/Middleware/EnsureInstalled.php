<?php

namespace App\Http\Middleware;

use App\Support\Setup;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends every request to the setup wizard until the installation is finished.
 */
class EnsureInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Setup::isInstalled()) {
            return $next($request);
        }

        if ($request->is('setup*') || $request->is('up')) {
            return $next($request);
        }

        if ($request->is('api/*')) {
            // An API client cannot follow a redirect to the setup wizard.
            abort(503, 'LightLMS is not installed yet.');
        }

        return redirect()->route('setup.index');
    }
}
