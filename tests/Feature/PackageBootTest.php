<?php

namespace Trail\Tests\Feature;

use Trail\Tests\TestCase;

class PackageBootTest extends TestCase
{
    public function test_step_helper_exists(): void
    {
        $this->assertTrue(function_exists('step'));
    }
}
