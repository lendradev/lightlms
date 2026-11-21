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

    public function test_registration_is_blocked_when_the_site_is_closed(): void
    {
        Setting::put(Setup::KEY_REGISTRATION, Setup::REGISTRATION_CLOSED, 'auth');

        $this->post('/register', [
            'name' => 'Closed Learner',
            'email' => 'closed@example.test',
            'password' => 'a-very-long-password',
            'password_confirmation' => 'a-very-long-password',
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'closed@example.test']);
        $this->assertGuest();
    }

    public function test_the_register_page_redirects_away_when_registration_is_closed(): void
    {
        Setting::put(Setup::KEY_REGISTRATION, Setup::REGISTRATION_CLOSED, 'auth');

        $this->get('/register')->assertRedirect(route('login'));
    }

    public function test_an_expired_invitation_carries_no_role(): void
    {
        Invitation::factory()->expired()->create([
            'email' => 'expired@example.test',
            'role' => UserRole::Admin->value,
            'token' => 'expired-token',
        ]);

        $this->post('/register', [
            'name' => 'Late Arrival',
            'email' => 'expired@example.test',
            'password' => 'a-very-long-password',
            'password_confirmation' => 'a-very-long-password',
            'invite_code' => 'expired-token',
        ])->assertSessionHasErrors('invite_code');

        $this->assertDatabaseMissing('users', ['email' => 'expired@example.test']);
    }

    public function test_an_invitation_that_was_already_used_carries_no_role(): void
    {
        Invitation::factory()->create([
            'email' => 'used@example.test',
            'role' => UserRole::Admin->value,
            'token' => 'used-token',
            'accepted_at' => now()->subDay(),
        ]);

        $this->post('/register', [
            'name' => 'Second Try',
            'email' => 'used@example.test',
            'password' => 'a-very-long-password',
            'password_confirmation' => 'a-very-long-password',
            'invite_code' => 'used-token',
        ])->assertSessionHasErrors('invite_code');

        $this->assertDatabaseMissing('users', ['email' => 'used@example.test']);
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

    public function test_repeated_wrong_passwords_are_throttled(): void
    {
        User::factory()->create([
            'email' => 'target@example.test',
            'password' => 'a-very-long-password',
        ]);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', [
                'email' => 'target@example.test',
                'password' => 'definitely-not-the-password',
            ])->assertSessionHasErrors('email');
        }

        // The sixth attempt is refused by the limiter, not by the credentials.
        $this->post('/login', [
            'email' => 'target@example.test',
            'password' => 'definitely-not-the-password',
        ])->assertStatus(429);
    }

    public function test_a_correct_password_still_works_after_a_few_wrong_ones(): void
    {
        $user = User::factory()->create([
            'email' => 'almost@example.test',
            'password' => 'a-very-long-password',
        ]);

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->post('/login', [
                'email' => 'almost@example.test',
                'password' => 'wrong-password-here',
            ]);
        }

        $this->post('/login', [
            'email' => 'almost@example.test',
            'password' => 'a-very-long-password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }
}
