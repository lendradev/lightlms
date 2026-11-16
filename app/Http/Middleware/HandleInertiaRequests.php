<?php

namespace App\Http\Middleware;

use App\Services\NotificationService;
use App\Support\Locale;
use App\Support\Setup;
use App\Support\Translations;
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
                'localeNames' => collect(Locale::available())->mapWithKeys(
                    fn (string $locale) => [$locale => Locale::name($locale)],
                )->all(),
                'registrationMode' => Setup::registrationMode(),
                'installed' => Setup::isInstalled(),
            ],
            'translations' => fn () => Translations::for(app()->getLocale()),
            'auth' => [
                'user' => $request->user()?->only(['id', 'name', 'email', 'role']),
            ],
            'notifications' => [
                'unread' => $request->user() === null
                    ? 0
                    : app(NotificationService::class)->unreadCount($request->user()),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
