<?php

namespace App\Support;

/**
 * Collects the interface strings for one locale.
 *
 * The front end receives one flat map, so a missing key in one language falls
 * back to English on the server instead of showing the raw key in the browser.
 */
class Translations
{
    /**
     * The files that make up the interface, in the order they are merged.
     *
     * @return list<string>
     */
    public static function groups(): array
    {
        return ['common', 'nav', 'auth', 'course', 'gradebook', 'admin', 'validation'];
    }

    /**
     * The flat key map for one locale, with English as the fallback.
     *
     * @return array<string, string>
     */
    public static function for(string $locale): array
    {
        $fallback = Locale::fallback();

        $strings = [];

        foreach (self::groups() as $group) {
            foreach (self::load($fallback, $group) as $key => $value) {
                $strings[$key] = $value;
            }
        }

        foreach (self::groups() as $group) {
            foreach (self::load($locale, $group) as $key => $value) {
                $strings[$key] = $value;
            }
        }

        return $strings;
    }

    /**
     * One string in one language, with English as the fallback.
     *
     * The interface keys are flat (`auth.signIn`), which Laravel's own `trans()`
     * cannot read, because it would look for a file called `auth.php` holding
     * a key called `signIn`. This reads the same flat map instead.
     *
     * @param  array<string, string|int>  $replace
     */
    public static function get(string $key, ?string $locale = null, array $replace = []): string
    {
        $strings = self::for($locale ?? app()->getLocale());
        $value = $strings[$key] ?? $key;

        foreach ($replace as $name => $replacement) {
            $value = str_replace(':'.$name, (string) $replacement, $value);
        }

        return $value;
    }

    /**
     * @return array<string, string>
     */
    private static function load(string $locale, string $group): array
    {
        if (! Locale::isAvailable($locale)) {
            return [];
        }

        $path = lang_path($locale.'/'.$group.'.php');

        if (! is_file($path)) {
            return [];
        }

        $values = require $path;

        return is_array($values) ? $values : [];
    }
}
