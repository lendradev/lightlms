<?php

namespace App\Http\Middleware;

use App\Support\Locale;
use App\Support\Setup;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'app' => [
                'name' => Setup::siteName(),
                'locale' => app()->getLocale(),
                'availableLocales' => Locale::available(),
                'registrationMode' => Setup::registrationMode(),
                'installed' => Setup::isInstalled(),
            ],
            'auth' => [
                'user' => $request->user()?->only(['id', 'name', 'email', 'role']),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
