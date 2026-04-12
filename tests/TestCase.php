<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    private int $baseOutputBufferLevel = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseOutputBufferLevel = ob_get_level();
    }

    protected function tearDown(): void
    {
        // Close only buffers leaked during this test run.
        while (ob_get_level() > $this->baseOutputBufferLevel) {
            ob_end_clean();
        }

        parent::tearDown();
    }
}
