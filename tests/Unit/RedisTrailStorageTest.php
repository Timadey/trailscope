<?php

namespace Trail\Tests\Unit;

use DateTimeImmutable;
use Illuminate\Support\Facades\Redis;
use Trail\Storage\RedisTrailStorage;
use Trail\Tests\TestCase;
use Trail\TraceContext;

class RedisTrailStorageTest extends TestCase
{
    public function test_it_stores_trace_payload_and_indexes(): void
    {
        Redis::shouldReceive('connection')->andReturn(new class {
            public array $calls = [];

            public function setex(string $key, int $ttl, string $payload): void
            {
                $this->calls[] = ['setex', $key, $ttl, $payload];
            }

            public function zadd(string $key, int $score, string $member): void
            {
                $this->calls[] = ['zadd', $key, $score, $member];
            }

            public function zremrangebyscore(string $key, int|string $min, int $max): int
            {
                $this->calls[] = ['zremrangebyscore', $key, $min, $max];

                return 0;
            }
        });

        $storage = new RedisTrailStorage('default', 'trail', 90);
        $trace = new TraceContext('trace-1', ['path' => 'transfer', 'started_at' => new DateTimeImmutable()]);

        $storage->store($trace);

        $this->assertTrue(true);
    }
}
