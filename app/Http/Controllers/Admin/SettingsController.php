<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Setup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Settings/Index', [
            'settings' => Setting::allAsArray(),
            'registrationModes' => [
                ['value' => Setup::REGISTRATION_OPEN, 'label' => 'Anyone can sign up'],
                ['value' => Setup::REGISTRATION_INVITE, 'label' => 'Invitation only'],
                ['value' => Setup::REGISTRATION_CLOSED, 'label' => 'Registration closed'],
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_name' => ['required', 'string', 'max:120'],
            'site_url' => ['required', 'url', 'max:255'],
            'registration' => ['required', 'in:'.Setup::REGISTRATION_OPEN.','.Setup::REGISTRATION_INVITE.','.Setup::REGISTRATION_CLOSED],
            'invite_code' => ['nullable', 'string', 'max:64'],
            'mail_from_address' => ['nullable', 'email', 'max:255'],
        ]);

        Setting::put(Setup::KEY_SITE_NAME, $validated['site_name'], 'general');
        Setting::put(Setup::KEY_SITE_URL, $validated['site_url'], 'general');
        Setting::put(Setup::KEY_REGISTRATION, $validated['registration'], 'auth');
        Setting::put(Setup::KEY_INVITE_CODE, $validated['invite_code'] ?? '', 'auth');
        Setting::put(Setup::KEY_MAIL_FROM, $validated['mail_from_address'] ?? '', 'general');

        config(['app.name' => $validated['site_name']]);

        return back()->with('success', 'Settings saved.');
    }
}
