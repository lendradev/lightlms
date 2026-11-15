<?php

namespace App\Support;

class Locale
{
    /**
     * @return array<int, string>
     */
    public static function available(): array
    {
        return array_keys(config('app.locales', ['en' => 'English']));
    }

    public static function fallback(): string
    {
        return config('app.fallback_locale', 'en');
    }
}
