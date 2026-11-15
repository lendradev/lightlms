<?php

namespace App\Support;

use App\Models\Setting;
use App\Models\User;

/**
 * Installation state of the site.
 *
 * A fresh install has no settings rows, so the setup wizard runs until the
 * administrator completes it. The same flag also gates public registration.
 */
class Setup
{
    public const KEY_INSTALLED = 'setup.installed';

    public const KEY_SITE_NAME = 'site.name';

    public const KEY_SITE_URL = 'site.url';

    public const KEY_REGISTRATION = 'auth.registration';

    public const KEY_INVITE_CODE = 'auth.invite_code';

    public const KEY_MAIL_FROM = 'mail.from_address';

    public const REGISTRATION_OPEN = 'open';

    public const REGISTRATION_INVITE = 'invite';

    public const REGISTRATION_CLOSED = 'closed';

    public static function isInstalled(): bool
    {
        return Setting::get(self::KEY_INSTALLED) === 'true';
    }

    public static function registrationMode(): string
    {
        return Setting::get(self::KEY_REGISTRATION, self::REGISTRATION_INVITE) ?? self::REGISTRATION_INVITE;
    }

    public static function isSelfRegistrationOpen(): bool
    {
        return self::registrationMode() === self::REGISTRATION_OPEN;
    }

    public static function isInviteOnly(): bool
    {
        return self::registrationMode() === self::REGISTRATION_INVITE;
    }

    /**
     * An invite code lets a visitor self-register even when the site is in
     * invite-only mode.
     */
    public static function isValidInviteCode(?string $code): bool
    {
        $expected = Setting::get(self::KEY_INVITE_CODE);

        if ($expected === null || $expected === '') {
            return false;
        }

        return hash_equals($expected, (string) $code);
    }

    public static function siteName(): string
    {
        return Setting::get(self::KEY_SITE_NAME, config('app.name')) ?? config('app.name');
    }

    public static function markInstalled(): void
    {
        Setting::put(self::KEY_INSTALLED, 'true', 'setup');
        Setting::put(self::KEY_SITE_NAME, config('app.name'), 'general');
        Setting::put(self::KEY_REGISTRATION, self::REGISTRATION_INVITE, 'auth');
    }

    /** @return array<string, string> */
    public static function defaults(): array
    {
        return [
            self::KEY_SITE_NAME => (string) config('app.name'),
            self::KEY_SITE_URL => (string) config('app.url'),
            self::KEY_REGISTRATION => self::REGISTRATION_INVITE,
            self::KEY_INVITE_CODE => '',
            self::KEY_MAIL_FROM => (string) config('mail.from.address'),
        ];
    }

    public static function bootstrapDefaults(): void
    {
        foreach (self::defaults() as $key => $value) {
            if (Setting::get($key) === null) {
                Setting::put($key, $value, str_contains($key, 'auth.') ? 'auth' : 'general');
            }
        }
    }

    public static function hasAdmin(): bool
    {
        return User::query()->where('role', 'admin')->exists();
    }
}
