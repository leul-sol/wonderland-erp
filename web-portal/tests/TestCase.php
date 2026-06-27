<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    /**
     * Assert an Inertia page after deferred props have loaded.
     *
     * @param  callable(\Inertia\Testing\AssertableInertia): void  $callback
     */
    protected function assertDeferredInertia(TestResponse $response, callable $callback): void
    {
        $response->assertInertia(fn ($page) => $page->loadDeferredProps($callback));
    }
}
