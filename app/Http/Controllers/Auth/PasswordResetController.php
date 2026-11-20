<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Setup;
use App\Support\Translations;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Setting a new password through a link sent by email.
 *
 * The request page always answers the same way, whether or not the address
 * belongs to an account. Otherwise the page would tell a stranger which
 * addresses are registered here.
 */
class PasswordResetController extends Controller
{
    /**
     * The page where a link is asked for.
     */
    public function showRequest(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'siteName' => Setup::siteName(),
        ]);
    }

    /**
     * Send the link.
     */
    public function sendLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        Password::sendResetLink($request->only('email'));

        // The status of the broker is not shown to the visitor. An unknown
        // address is not a mistake the person made, and telling them so would
        // turn this page into a way to look up who has an account here.
        return back()->with('success', Translations::get('auth.resetLinkSent'));
    }

    /**
     * The page where the new password is typed.
     *
     * A link that was already used, or that was typed wrong, sends the
     * visitor back to the request page with a plain message. A dead link
     * is an ordinary situation, not an error worth a page of its own.
     */
    public function showReset(Request $request, string $token): Response|RedirectResponse
    {
        $email = (string) $request->query('email', '');

        if (! $this->tokenIsValid($email, $token)) {
            return redirect()->route('password.request')
                ->with('error', Translations::get('auth.resetLinkInvalid'));
        }

        return Inertia::render('Auth/ResetPassword', [
            'email' => $email,
            'token' => $token,
            'siteName' => Setup::siteName(),
        ]);
    }

    /**
     * Set the new password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(12)],
        ]);

        $status = Password::broker()->reset(
            [
                'token' => $validated['token'],
                'email' => $validated['email'],
                'password' => $validated['password'],
            ],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));

                // The remember token is rotated, so a "remember me" cookie
                // that was handed out with the old password stops working.
                // Sessions that are already open are ended by the session
                // middleware, which compares the stored password hash with
                // the one the account has now.
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => Translations::get('auth.resetLinkInvalid'),
            ]);
        }

        return redirect()->route('login')->with('success', Translations::get('auth.resetDone'));
    }

    /**
     * Whether the token is the current one for that address.
     */
    private function tokenIsValid(string $email, string $token): bool
    {
        if ($email === '' || $token === '') {
            return false;
        }

        $user = Password::broker()->getUser(['email' => $email]);

        return $user !== null && Password::broker()->tokenExists($user, $token);
    }
}
