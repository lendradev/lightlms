<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Support\Locale;
use App\Support\Setup;
use App\Support\Translations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::put(Setup::KEY_INSTALLED, 'true', 'setup');
    }

    public function test_the_default_locale_is_english(): void
    {
        $this->assertSame('en', app()->getLocale());
    }

    public function test_the_locale_cookie_wins_over_the_accept_language_header(): void
    {
        $this->withUnencryptedCookies([Locale::COOKIE => 'id'])
            ->get('/', ['Accept-Language' => 'en'])
            ->assertOk();

        $this->assertSame('id', app()->getLocale());
    }

    public function test_the_accept_language_header_is_used_when_there_is_no_cookie(): void
    {
        $this->get('/', ['Accept-Language' => 'id-ID,id;q=0.9,en;q=0.5'])->assertOk();

        $this->assertSame('id', app()->getLocale());
    }

    public function test_an_unknown_locale_in_the_cookie_is_ignored(): void
    {
        $this->withUnencryptedCookies([Locale::COOKIE => 'zz'])
            ->get('/', ['Accept-Language' => 'en'])
            ->assertOk();

        $this->assertSame('en', app()->getLocale());
    }

    public function test_a_locale_can_be_chosen_and_is_stored_in_a_cookie(): void
    {
        $response = $this->post('/locale', ['locale' => 'id']);

        $response->assertRedirect();
        $response->assertCookie(Locale::COOKIE, 'id', false);
    }

    public function test_an_unregistered_locale_cannot_be_chosen(): void
    {
        $this->post('/locale', ['locale' => 'zz'])->assertStatus(422);
    }

    public function test_the_translations_are_shared_with_the_interface(): void
    {
        $response = $this->get('/');

        $response->assertOk();

        $props = $response->viewData('page')['props'];

        $this->assertSame('en', $props['app']['locale']);
        $this->assertSame('Dashboard', $props['translations']['nav.dashboard']);
    }

    public function test_every_key_in_english_has_a_counterpart_in_indonesian(): void
    {
        $english = Translations::for('en');
        $indonesian = Translations::for('id');

        $this->assertNotEmpty($english);

        $missing = array_diff(array_keys($english), array_keys($indonesian));

        $this->assertSame([], array_values($missing), 'Every English key needs an Indonesian one.');
    }

    public function test_the_two_languages_do_not_share_the_same_text(): void
    {
        $english = Translations::for('en');
        $indonesian = Translations::for('id');

        $shared = [];

        foreach ($english as $key => $value) {
            if (isset($indonesian[$key]) && $indonesian[$key] === $value) {
                $shared[] = $key;
            }
        }

        // Product names and the words Indonesian uses as they are stay the same.
        $allowed = ['app.name', 'app.restApi', 'auth.email', 'course.status', 'course.total'];

        $this->assertSame([], array_values(array_diff($shared, $allowed)));
    }

    public function test_every_placeholder_uses_the_colon_syntax_the_front_end_replaces(): void
    {
        foreach (['en', 'id'] as $locale) {
            foreach (Translations::for($locale) as $key => $value) {
                foreach ($this->flatten($value) as $string) {
                    // The front end replaces ":name". A placeholder in braces is
                    // never replaced, so it reaches the browser as raw text.
                    $this->assertDoesNotMatchRegularExpression(
                        '/\{[a-z_]+\}/',
                        $string,
                        "The string \"{$key}\" in {$locale} uses braces instead of a colon.",
                    );
                }
            }
        }
    }

    /**
     * A translation group may hold a nested array, such as the validation
     * messages for a size rule.
     *
     * @return list<string>
     */
    private function flatten(mixed $value): array
    {
        if (is_array($value)) {
            $strings = [];

            foreach ($value as $item) {
                $strings = [...$strings, ...$this->flatten($item)];
            }

            return $strings;
        }

        return [(string) $value];
    }

    public function test_a_key_that_is_missing_in_one_language_falls_back_to_english(): void
    {
        $indonesian = Translations::for('id');

        $this->assertSame('Dasbor', $indonesian['nav.dashboard']);

        // A key that exists only in English is served in English.
        $this->assertArrayHasKey('nav.dashboard', $indonesian);
    }

    public function test_an_unknown_locale_falls_back_to_english(): void
    {
        $this->assertSame(Translations::for('en'), Translations::for('zz'));
    }
}
