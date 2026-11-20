<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->rateLimits();
    }

    /**
     * The limits for the API.
     *
     * A token is counted per token, not per address, so one busy integration
     * cannot lock out the other people behind the same network.
     */
    private function rateLimits(): void
    {
        RateLimiter::for('api', function (Request $request) {
            $token = $request->user()?->currentAccessToken()?->id;

            return [
                Limit::perMinute(60)->by('token:'.($token ?? $request->ip())),
                Limit::perDay(1000)->by('token:'.($token ?? $request->ip())),
            ];
        });

        // A wrong password must not be free to repeat. The limit is counted
        // per address and per email, so one attacker cannot lock a whole
        // office out and one account cannot be guessed from many addresses.
        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->input('email');

            return [
                Limit::perMinute(5)->by('login-ip:'.$request->ip()),
                Limit::perMinute(5)->by('login-email:'.mb_strtolower($email)),
                Limit::perHour(20)->by('login-ip-hour:'.$request->ip()),
            ];
        });

        // Creating an account is cheap for the attacker and expensive for the
        // server, so it gets a tighter limit than a sign-in.
        RateLimiter::for('register', function (Request $request) {
            return [
                Limit::perHour(5)->by('register-ip:'.$request->ip()),
                Limit::perDay(20)->by('register-ip-day:'.$request->ip()),
            ];
        });

        // The setup wizard creates the first administrator. It is open only
        // until the site is installed, but it still must not be guessable.
        RateLimiter::for('setup', function (Request $request) {
            return [
                Limit::perHour(5)->by('setup-ip:'.$request->ip()),
            ];
        });

        // Asking for a reset link sends an email, so it is limited per
        // address and per email address. One address must not be able to
        // flood a mailbox, and one mailbox must not be flooded from many
        // addresses.
        RateLimiter::for('password-email', function (Request $request) {
            $email = mb_strtolower((string) $request->input('email'));

            return [
                Limit::perMinute(5)->by('password-email-ip:'.$request->ip()),
                Limit::perMinute(5)->by('password-email-address:'.$email),
                Limit::perHour(20)->by('password-email-ip-hour:'.$request->ip()),
            ];
        });

        // Trying tokens is cheap for the attacker, so it gets its own limit.
        RateLimiter::for('password-update', function (Request $request) {
            return [
                Limit::perMinute(10)->by('password-update-ip:'.$request->ip()),
            ];
        });
    }
}
