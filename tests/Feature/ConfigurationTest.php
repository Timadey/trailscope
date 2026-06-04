<?php

namespace Trail\Tests\Feature;

use Trail\Tests\TestCase;

class ConfigurationTest extends TestCase
{
    public function test_trail_config_is_loaded(): void
    {
        $this->assertTrue(config('trail.enabled'));
        $this->assertSame(90, config('trail.retention.days'));
        $this->assertSame('trail_users', config('trail.access.mode'));
        $this->assertSame('database', config('trail.storage.driver'));
        $this->assertSame('redis', config('trail.storage.redis.driver'));
    }
}
