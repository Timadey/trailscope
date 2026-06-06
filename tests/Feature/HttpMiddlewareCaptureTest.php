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

        Route::middleware('trail')->post('/alias-transfer', fn () => response()->json(['ok' => true]));

        Route::middleware('trail')->get('/health', fn () => response()->json(['ok' => true]));
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

    public function test_trail_middleware_alias_records_request_trace(): void
    {
        config(['trail.storage.write_mode' => 'sync']);

        $this->postJson('/alias-transfer')->assertOk();

        $this->assertTrue(TrailTrace::query()->where('path', 'alias-transfer')->exists());
    }

    public function test_excluded_paths_are_not_recorded(): void
    {
        config([
            'trail.storage.write_mode' => 'sync',
            'trail.capture.except_paths' => ['health'],
        ]);

        $this->getJson('/health')->assertOk();

        $this->assertFalse(TrailTrace::query()->where('path', 'health')->exists());
    }
}
