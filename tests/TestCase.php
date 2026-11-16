<?php

namespace Tests;

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
}
