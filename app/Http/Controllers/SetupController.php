<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use App\Support\Setup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class SetupController extends Controller
{
    public function index(): Response|RedirectResponse
    {
        if (Setup::isInstalled()) {
            return redirect()->route('login');
        }

        return Inertia::render('Setup/Welcome', [
            'requirements' => $this->requirements(),
            'hasAdmin' => Setup::hasAdmin(),
        ]);
    }

    public function install(Request $request): RedirectResponse
    {
        if (Setup::isInstalled()) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'site_name' => ['required', 'string', 'max:120'],
            'site_url' => ['required', 'url', 'max:255'],
            'admin_name' => ['required', 'string', 'max:120'],
            'admin_email' => ['required', 'email', 'max:255'],
            'admin_password' => ['required', 'string', 'min:12', 'confirmed'],
            'registration' => ['required', 'in:'.Setup::REGISTRATION_OPEN.','.Setup::REGISTRATION_INVITE],
            'invite_code' => ['nullable', 'string', 'max:64'],
        ]);

        $admin = DB::transaction(function () use ($validated): User {
            $admin = User::query()->create([
                'name' => $validated['admin_name'],
                'email' => $validated['admin_email'],
                'password' => Hash::make($validated['admin_password']),
                'email_verified_at' => now(),
                'role' => 'admin',
            ]);

            Setting::put(Setup::KEY_INSTALLED, 'true', 'setup');
            Setting::put(Setup::KEY_SITE_NAME, $validated['site_name'], 'general');
            Setting::put(Setup::KEY_SITE_URL, $validated['site_url'], 'general');
            Setting::put(Setup::KEY_REGISTRATION, $validated['registration'], 'auth');
            Setting::put(Setup::KEY_INVITE_CODE, $validated['invite_code'] ?? '', 'auth');
            Setting::put('setup.admin_id', (string) $admin->id, 'setup');

            config(['app.name' => $validated['site_name']]);

            return $admin;
        });

        Auth::login($admin);

        return redirect()->route('dashboard');
    }

    /** @return array<int, array{label: string, ok: bool, hint: string}> */
    private function requirements(): array
    {
        $checks = [
            [
                'label' => 'PHP 8.3 or newer',
                'ok' => version_compare(PHP_VERSION, '8.3.0', '>='),
                'hint' => PHP_VERSION,
            ],
            [
                'label' => 'PDO database driver',
                'ok' => in_array('mysql', \PDO::getAvailableDrivers(), true)
                    || in_array('pgsql', \PDO::getAvailableDrivers(), true)
                    || in_array('sqlite', \PDO::getAvailableDrivers(), true),
                'hint' => implode(', ', \PDO::getAvailableDrivers()),
            ],
            [
                'label' => 'Writable storage directory',
                'ok' => is_writable(storage_path()),
                'hint' => storage_path(),
            ],
            [
                'label' => 'Application key set',
                'ok' => config('app.key') !== null && config('app.key') !== '',
                'hint' => 'APP_KEY',
            ],
            [
                'label' => 'Writable uploads directory',
                'ok' => is_writable(storage_path('app/public')),
                'hint' => storage_path('app/public'),
            ],
        ];

        return $checks;
    }
}
