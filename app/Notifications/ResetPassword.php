<?php

namespace App\Notifications;

use App\Models\Setting;
use App\Support\Locale;
use App\Support\Setup;
use App\Support\Translations;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The email that carries the link for setting a new password.
 *
 * The text follows the language of the account, because the person reading
 * the message is the one who asked for it.
 */
class ResetPassword extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $token,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        // The message is read by the account holder, so it follows the
        // language of the account and not the language of the request that
        // happened to trigger it.
        $locale = $this->localeFor($notifiable);

        return (new MailMessage)
            ->from($this->fromAddress(), $this->fromName())
            ->subject(Translations::get('auth.resetEmailSubject', $locale))
            ->line(Translations::get('auth.resetEmailIntro', $locale))
            ->action(Translations::get('auth.resetEmailAction', $locale), $url)
            ->line(Translations::get('auth.resetEmailExpires', $locale, ['count' => $minutes]))
            ->line(Translations::get('auth.resetEmailIgnored', $locale));
    }

    /**
     * The address the mail is sent from.
     *
     * The administrator can set it in the panel, so the setting is what the
     * message uses and not the value from the environment file.
     */
    private function fromAddress(): string
    {
        $configured = Setting::get(Setup::KEY_MAIL_FROM);

        return is_string($configured) && filter_var($configured, FILTER_VALIDATE_EMAIL)
            ? $configured
            : (string) config('mail.from.address');
    }

    private function fromName(): string
    {
        $name = Setup::siteName();

        return $name !== '' ? $name : (string) config('mail.from.name');
    }

    /**
     * The language of the account, or the fallback when it has none.
     */
    private function localeFor(object $notifiable): string
    {
        $locale = $notifiable->locale ?? null;

        return is_string($locale) && Locale::isAvailable($locale) ? $locale : Locale::fallback();
    }
}
