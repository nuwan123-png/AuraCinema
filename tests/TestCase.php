<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // CI runs the tests before `npm run build`, so there is no Vite manifest yet.
        $this->withoutVite();
    }
}
