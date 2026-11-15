<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\User;
use App\Support\Setup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function showLogin(): Response
    {
        return Inertia::render('Auth/Login', [
            'registrationMode' => Setup::registrationMode(),
            'siteName' => Setup::siteName(),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Those credentials do not match our records.',
            ]);
        }

        $request->session()->regenerate();

        $user = $request->user();
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('dashboard'));
    }

    public function showRegister(Request $request): Response|RedirectResponse
    {
        if (Setup::isSelfRegistrationOpen()) {
            return Inertia::render('Auth/Register', [
                'requiresInviteCode' => false,
                'inviteCode' => (string) $request->query('invite', ''),
                'siteName' => Setup::siteName(),
            ]);
        }

        $token = $request->query('invite');

        if ($token !== null) {
            $invitation = Invitation::query()->where('token', $token)->first();

            if ($invitation !== null && $invitation->isPending()) {
                return Inertia::render('Auth/Register', [
                    'requiresInviteCode' => false,
                    'inviteCode' => $token,
                    'invitedEmail' => $invitation->email,
                    'invitedRole' => $invitation->role,
                    'siteName' => Setup::siteName(),
                ]);
            }
        }

        return Inertia::render('Auth/Register', [
            'requiresInviteCode' => true,
            'inviteCode' => '',
            'siteName' => Setup::siteName(),
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        $mode = Setup::registrationMode();

        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ];

        if ($mode === Setup::REGISTRATION_INVITE) {
            $rules['invite_code'] = ['required', 'string', 'max:64'];
        }

        $validated = $request->validate($rules);

        $role = UserRole::Student;
        $invitation = null;

        if ($mode === Setup::REGISTRATION_INVITE) {
            $invitation = Invitation::query()
                ->where('email', $validated['email'])
                ->where('token', $validated['invite_code'])
                ->first();

            $inviteCodeValid = Setup::isValidInviteCode($validated['invite_code']);

            if ($invitation === null && ! $inviteCodeValid) {
                throw ValidationException::withMessages([
                    'invite_code' => 'This invitation is not valid.',
                ]);
            }

            if ($invitation !== null) {
                $role = UserRole::from($invitation->role);
                $invitation->forceFill(['accepted_at' => now()])->save();
            }
        }

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
            'role' => $role,
        ]);

        Auth::login($user);

        return redirect()->route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
