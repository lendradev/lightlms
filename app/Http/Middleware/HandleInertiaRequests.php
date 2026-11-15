<?php

namespace App\Http\Middleware;

use App\Support\Locale;
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
                'name' => config('app.name'),
                'locale' => app()->getLocale(),
                'availableLocales' => Locale::available(),
            ],

            'auth' => [
                'user' => $request->user()?->only(['id', 'name', 'email', 'role']),
            ],
        ];
    }
}
