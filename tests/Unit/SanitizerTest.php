<?php

namespace Trail\Tests\Unit;

use Trail\Support\Sanitizer;
use Trail\Tests\TestCase;

class SanitizerTest extends TestCase
{
    public function test_it_masks_sensitive_keys_recursively(): void
    {
        $sanitizer = new Sanitizer(['password', 'token'], '[Filtered]');

        $result = $sanitizer->clean([
            'email' => 'user@example.test',
            'password' => 'secret',
            'meta' => ['token' => 'abc'],
        ]);

        $this->assertSame('user@example.test', $result['email']);
        $this->assertSame('[Filtered]', $result['password']);
        $this->assertSame('[Filtered]', $result['meta']['token']);
    }

    public function test_it_truncates_large_strings(): void
    {
        $sanitizer = new Sanitizer([], '[Filtered]', 5);

        $this->assertSame('abcde...[truncated]', $sanitizer->clean('abcdef'));
    }
}
