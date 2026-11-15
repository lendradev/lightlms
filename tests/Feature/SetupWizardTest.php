<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Support\Setup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetupWizardTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_fresh_install_redirects_every_page_to_the_setup_wizard(): void
    {
        $this->get('/')->assertRedirect(route('setup.index'));
    }

    public function test_the_setup_wizard_lists_system_requirements(): void
    {
        $this->get(route('setup.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Setup/Welcome')->has('requirements'));
    }

    public function test_completing_the_setup_creates_an_admin_and_marks_the_site_installed(): void
    {
        $this->post(route('setup.install'), [
            'site_name' => 'Acme Academy',
            'site_url' => 'https://lms.test',
            'admin_name' => 'Site Owner',
            'admin_email' => 'owner@lms.test',
            'admin_password' => 'a-very-long-password',
            'admin_password_confirmation' => 'a-very-long-password',
            'registration' => Setup::REGISTRATION_INVITE,
            'invite_code' => 'welcome-code',
        ])->assertRedirect(route('dashboard'));

        $this->assertTrue(Setup::isInstalled());
        $this->assertDatabaseHas('users', ['email' => 'owner@lms.test', 'role' => 'admin']);
        $this->assertAuthenticated();
    }

    public function test_the_setup_wizard_cannot_run_twice(): void
    {
        Setting::put(Setup::KEY_INSTALLED, 'true', 'setup');

        $this->get(route('setup.index'))->assertRedirect(route('login'));
    }

    public function test_the_site_is_reachable_once_installed(): void
    {
        Setting::put(Setup::KEY_INSTALLED, 'true', 'setup');
        User::factory()->admin()->create();

        $this->get('/')->assertOk();
    }
}
