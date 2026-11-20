<?php

namespace Tests;

use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // The PHP suite must not depend on a front-end build. Without a
        // manifest, the root view and the error page both fail to render.
        $this->withoutVite();
    }

    /**
     * Sign a user in for the next request.
     *
     * The session middleware ends a session whose password hash no longer
     * matches the account it belongs to. Switching the acting user does not
     * change the hash the session was opened with, so that hash is dropped
     * here and the middleware stores the one of the new user.
     */
    public function actingAs(UserContract $user, $guard = null)
    {
        $this->app['session']->forget(
            'password_hash_'.$this->app['auth']->getDefaultDriver(),
        );

        return parent::actingAs($user, $guard);
    }
}
