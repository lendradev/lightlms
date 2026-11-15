<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Invitation;
use App\Models\Setting;
use App\Models\User;
use App\Support\Setup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::put(Setup::KEY_INSTALLED, 'true', 'setup');
        Setting::put(Setup::KEY_REGISTRATION, Setup::REGISTRATION_INVITE, 'auth');
    }

    public function test_a_guest_sees_the_login_page(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Auth/Login'));
    }

    public function test_a_user_can_sign_in(): void
    {
        $user = User::factory()->create(['password' => 'a-very-long-password']);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'a-very-long-password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $user = User::factory()->create(['password' => 'a-very-long-password']);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_registration_is_blocked_when_the_site_is_invite_only(): void
    {
        $this->post('/register', [
            'name' => 'New Learner',
            'email' => 'learner@example.test',
            'password' => 'a-very-long-password',
            'password_confirmation' => 'a-very-long-password',
        ])->assertSessionHasErrors('invite_code');

        $this->assertGuest();
    }

    public function test_an_invited_user_can_register_with_a_matching_token(): void
    {
        Invitation::factory()->create([
            'email' => 'invited@example.test',
            'role' => UserRole::Instructor->value,
            'token' => 'invite-token',
        ]);

        $this->post('/register', [
            'name' => 'Invited Teacher',
            'email' => 'invited@example.test',
            'password' => 'a-very-long-password',
            'password_confirmation' => 'a-very-long-password',
            'invite_code' => 'invite-token',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', [
            'email' => 'invited@example.test',
            'role' => UserRole::Instructor->value,
        ]);
    }

    public function test_open_registration_lets_anyone_sign_up(): void
    {
        Setting::put(Setup::KEY_REGISTRATION, Setup::REGISTRATION_OPEN, 'auth');

        $this->post('/register', [
            'name' => 'Open Learner',
            'email' => 'open@example.test',
            'password' => 'a-very-long-password',
            'password_confirmation' => 'a-very-long-password',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', ['email' => 'open@example.test']);
    }

    public function test_a_user_can_sign_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_the_dashboard_requires_a_signed_in_user(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }
}
