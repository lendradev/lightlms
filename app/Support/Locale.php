<?php

namespace App\Support;

use Illuminate\Support\Facades\Cookie;

class Locale
{
    public const COOKIE = 'lightlms-locale';

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

    /**
     * The name of a locale, in that locale's own language.
     */
    public static function name(string $locale): string
    {
        return config('app.locales.'.$locale, $locale);
    }

    public static function isAvailable(string $locale): bool
    {
        return in_array($locale, self::available(), true);
    }

    /**
     * The locale in use for this request.
     */
    public static function current(): string
    {
        return app()->getLocale();
    }

    /**
     * The first supported locale out of an Accept-Language header.
     *
     * The header can carry a quality list, so the order matters: `id-ID,id;q=0.9`
     * has to pick Indonesian rather than the first tag it sees.
     *
     * @param  string|null  $header
     */
    public static function fromHeader(?string $header): ?string
    {
        if ($header === null || $header === '') {
            return null;
        }

        $tags = [];

        foreach (explode(',', $header) as $part) {
            $pieces = explode(';', trim($part));

            $tag = strtolower(trim($pieces[0]));
            $quality = 1.0;

            if (isset($pieces[1]) && str_contains($pieces[1], 'q=')) {
                $quality = (float) substr($pieces[1], strpos($pieces[1], 'q=') + 2);
            }

            if ($tag !== '' && $quality > 0) {
                $tags[$tag] = $quality;
            }
        }

        arsort($tags);

        foreach (array_keys($tags) as $tag) {
            $base = explode('-', $tag)[0];

            if (self::isAvailable($base)) {
                return $base;
            }
        }

        return null;
    }

    /**
     * Remember the locale in a cookie that lasts a year.
     */
    public static function remember(string $locale): void
    {
        Cookie::queue(Cookie::forever(self::COOKIE, $locale));
    }
}
