<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Notifications\ResetPassword;
use App\Support\Setup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::put(Setup::KEY_INSTALLED, 'true', 'setup');
    }

    public function test_the_request_page_is_reachable(): void
    {
        $this->get('/forgot-password')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Auth/ForgotPassword'));
    }

    public function test_a_reset_link_is_sent_for_a_known_address(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'forgetful@example.test']);

        $this->post('/forgot-password', ['email' => 'forgetful@example.test'])
            ->assertRedirect()
            ->assertSessionHas('success');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_an_unknown_address_gets_the_same_answer(): void
    {
        Notification::fake();

        $this->post('/forgot-password', ['email' => 'nobody@example.test'])
            ->assertRedirect()
            ->assertSessionHas('success');

        Notification::assertNothingSent();
    }

    public function test_the_link_carries_a_token_that_opens_the_form(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'forgetful@example.test']);

        $this->post('/forgot-password', ['email' => 'forgetful@example.test']);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification): bool {
            $this->get('/reset-password/'.$notification->token.'?email=forgetful@example.test')
                ->assertOk()
                ->assertInertia(fn ($page) => $page->component('Auth/ResetPassword'));

            return true;
        });
    }

    public function test_the_password_is_changed_and_the_new_one_works(): void
    {
        Notification::fake();

        User::factory()->create([
            'email' => 'forgetful@example.test',
            'password' => 'a-very-long-password',
        ]);

        $this->post('/forgot-password', ['email' => 'forgetful@example.test']);

        $token = $this->lastToken();

        $this->post('/reset-password', [
            'token' => $token,
            'email' => 'forgetful@example.test',
            'password' => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
        ])->assertRedirect(route('login'));

        $user = User::query()->where('email', 'forgetful@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('a-brand-new-password', $user->password));

        $this->post('/login', [
            'email' => 'forgetful@example.test',
            'password' => 'a-brand-new-password',
        ])->assertRedirect(route('dashboard'));
    }

    public function test_a_wrong_token_is_refused(): void
    {
        User::factory()->create(['email' => 'forgetful@example.test']);

        // A dead link sends the visitor back to the request page with a plain
        // message, so the address can be typed again.
        $this->get('/reset-password/not-a-real-token?email=forgetful@example.test')
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('error');
    }

    public function test_the_token_only_works_once(): void
    {
        Notification::fake();

        User::factory()->create([
            'email' => 'forgetful@example.test',
            'password' => 'a-very-long-password',
        ]);

        $this->post('/forgot-password', ['email' => 'forgetful@example.test']);
        $token = $this->lastToken();

        $this->post('/reset-password', [
            'token' => $token,
            'email' => 'forgetful@example.test',
            'password' => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
        ])->assertRedirect(route('login'));

        // The same link a second time must not work any more.
        $this->post('/reset-password', [
            'token' => $token,
            'email' => 'forgetful@example.test',
            'password' => 'yet-another-password',
            'password_confirmation' => 'yet-another-password',
        ])->assertSessionHasErrors('email');
    }

    public function test_the_new_password_has_to_be_long_enough_and_match(): void
    {
        Notification::fake();

        User::factory()->create(['email' => 'forgetful@example.test']);

        $this->post('/forgot-password', ['email' => 'forgetful@example.test']);
        $token = $this->lastToken();

        $this->post('/reset-password', [
            'token' => $token,
            'email' => 'forgetful@example.test',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');

        $this->post('/reset-password', [
            'token' => $token,
            'email' => 'forgetful@example.test',
            'password' => 'a-brand-new-password',
            'password_confirmation' => 'a-different-password',
        ])->assertSessionHasErrors('password');
    }

    public function test_asking_for_a_link_repeatedly_is_throttled(): void
    {
        Notification::fake();

        User::factory()->create(['email' => 'forgetful@example.test']);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/forgot-password', ['email' => 'forgetful@example.test'])
                ->assertRedirect();
        }

        $this->post('/forgot-password', ['email' => 'forgetful@example.test'])
            ->assertStatus(429);
    }

    public function test_the_reset_email_follows_the_language_of_the_account(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'forgetful@example.test',
            'locale' => 'id',
        ]);

        $this->post('/forgot-password', ['email' => 'forgetful@example.test']);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
            $mail = $notification->toMail($user);

            return str_contains($mail->subject, 'Atur ulang kata sandi');
        });
    }

    public function test_a_session_that_was_opened_before_the_reset_no_longer_works(): void
    {
        Notification::fake();

        User::factory()->create([
            'email' => 'forgetful@example.test',
            'password' => 'a-very-long-password',
        ]);

        // One browser signs in and keeps its session.
        $this->post('/login', [
            'email' => 'forgetful@example.test',
            'password' => 'a-very-long-password',
        ])->assertRedirect(route('dashboard'));

        $openSession = session()->all();

        // Another browser asks for the link and sets the new password. The
        // test client keeps a single session, so the first one is put aside
        // and restored afterwards.
        $this->app['auth']->forgetGuards();
        session()->flush();

        $this->post('/forgot-password', ['email' => 'forgetful@example.test']);

        $token = $this->lastToken();

        $this->post('/reset-password', [
            'token' => $token,
            'email' => 'forgetful@example.test',
            'password' => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
        ])->assertRedirect(route('login'));

        // The first browser comes back with the session it had. The session
        // middleware compares the password hash it was opened with, so the
        // old session must not reach a protected page any more.
        $this->app['auth']->forgetGuards();
        session()->flush();
        session()->put($openSession);

        $this->get('/dashboard')
            ->assertRedirect(route('login'));
    }

    public function test_the_remember_token_is_rotated_after_the_reset(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'forgetful@example.test',
            'password' => 'a-very-long-password',
        ]);

        $before = $user->remember_token;

        $this->post('/forgot-password', ['email' => 'forgetful@example.test']);
        $token = $this->lastToken();

        $this->post('/reset-password', [
            'token' => $token,
            'email' => 'forgetful@example.test',
            'password' => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
        ])->assertRedirect(route('login'));

        // The old remember token cannot bring anybody back in.
        $this->assertNotSame($before, $user->fresh()->remember_token);
    }

    /**
     * The token of the notification that was sent last.
     */
    private function lastToken(): string
    {
        $token = null;
        $user = User::query()->where('email', 'forgetful@example.test')->firstOrFail();

        Notification::assertSentTo(
            $user,
            ResetPassword::class,
            function (ResetPassword $notification) use (&$token): bool {
                $token = $notification->token;

                return true;
            },
        );

        return (string) $token;
    }
}
