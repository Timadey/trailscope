<?php

namespace Trail\Tests\Feature;

use Trail\Tests\TestCase;

class ConfigurationTest extends TestCase
{
    public function test_trail_config_includes_user_guidance_comments(): void
    {
        $config = file_get_contents(__DIR__ . '/../../config/trail.php');

        $this->assertStringContainsString('TRAIL_ENABLED=false', $config);
        $this->assertStringContainsString('Supported: trail_users, gate, signed_url.', $config);
        $this->assertStringContainsString('Example: TRAIL_IP_ALLOWLIST=127.0.0.1,10.0.0.5', $config);
        $this->assertStringContainsString('Supported: sync, after_response, queue.', $config);
        $this->assertStringContainsString('Example: TRAIL_REDIS_PREFIX=trail', $config);
    }

    public function test_trail_config_is_loaded(): void
    {
        $this->assertTrue(config('trail.enabled'));
        $this->assertSame(90, config('trail.retention.days'));
        $this->assertSame('trail_users', config('trail.access.mode'));
        $this->assertSame('database', config('trail.storage.driver'));
        $this->assertSame('redis', config('trail.storage.redis.driver'));
    }
}
