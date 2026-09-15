<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Tests assert on routing, props and data - never on asset URLs. Without
        // this they would need a built public/build/manifest.json, which would
        // make `php artisan test` depend on having run `npm run build` first.
        $this->withoutVite();
    }
}
