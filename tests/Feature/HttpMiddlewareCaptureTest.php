<?php

namespace Trail\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Trail\Http\Middleware\RecordTrail;
use Trail\Models\TrailTrace;
use Trail\Tests\TestCase;

class HttpMiddlewareCaptureTest extends TestCase
{
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    protected function defineRoutes($router): void
    {
        Route::middleware(RecordTrail::class)->post('/transfer', function () {
            step('handler reached', ['reference' => 'abc']);

            return response()->json(['ok' => true]);
        });
    }

    public function test_it_records_request_trace(): void
    {
        config(['trail.storage.write_mode' => 'sync']);

        $this->postJson('/transfer', ['amount' => 1000])->assertOk();

        $trace = TrailTrace::query()->where('path', 'transfer')->firstOrFail();

        $this->assertSame('POST', $trace->method);
        $this->assertSame(200, $trace->status_code);
        $this->assertTrue($trace->steps()->where('message', 'handler reached')->exists());
    }
}
