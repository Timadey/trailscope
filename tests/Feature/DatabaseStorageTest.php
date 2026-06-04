<?php

namespace Trail\Tests\Feature;

use Trail\Models\TrailTrace;
use Trail\Storage\TrailStorageDriver;
use Trail\Tests\TestCase;
use Trail\TraceContext;

class DatabaseStorageTest extends TestCase
{
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    public function test_it_stores_trace_and_steps(): void
    {
        $trace = new TraceContext('trace-1', [
            'method' => 'POST',
            'path' => '/transfer',
            'status_code' => 200,
        ]);
        $trace->addStep('checking wallet', ['value_1' => 5000]);

        app(TrailStorageDriver::class)->store($trace);

        $stored = TrailTrace::query()->where('trace_id', 'trace-1')->firstOrFail();

        $this->assertSame('/transfer', $stored->path);
        $this->assertSame('checking wallet', $stored->steps()->first()->message);
    }
}
