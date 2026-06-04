<?php

namespace Trail\Tests\Unit;

use Exception;
use Trail\Context\ContextNormalizer;
use Trail\Support\Sanitizer;
use Trail\Tests\TestCase;

class ContextNormalizerTest extends TestCase
{
    public function test_it_normalizes_scalars_arrays_and_exceptions(): void
    {
        $normalizer = new ContextNormalizer(new Sanitizer(['password'], '[Filtered]'));

        $result = $normalizer->normalize([
            5000,
            ['password' => 'secret'],
            new Exception('Provider failed'),
        ]);

        $this->assertSame(5000, $result['value_1']);
        $this->assertSame('[Filtered]', $result['context_2']['password']);
        $this->assertSame(Exception::class, $result['exception_3']['class']);
        $this->assertSame('Provider failed', $result['exception_3']['message']);
    }
}
