<?php

namespace Trail\Tests\Feature;

use Trail\Tests\TestCase;

class PackageBootTest extends TestCase
{
    public function test_package_metadata_uses_trailscope_name(): void
    {
        $composer = json_decode(file_get_contents(__DIR__ . '/../../composer.json'), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('timadey/trailscope', $composer['name']);
        $this->assertSame('Laravel request tracing and user journey observability with step logging, dashboard insights, and database or Redis storage.', $composer['description']);
    }

    public function test_step_helper_exists(): void
    {
        $this->assertTrue(function_exists('step'));
    }
}
