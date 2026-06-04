<?php

namespace Trail\Tests\Feature;

use DateTimeInterface;
use Exception;
use Illuminate\Support\Facades\Route;
use Trail\Http\Middleware\RecordTrail;
use Trail\Storage\TrailStorageDriver;
use Trail\Tests\TestCase;
use Trail\TraceContext;

class StorageFailureTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        Route::middleware(RecordTrail::class)->get('/ok', fn () => 'ok');
    }

    public function test_storage_failure_does_not_break_business_response(): void
    {
        $this->app->bind(TrailStorageDriver::class, fn () => new class implements TrailStorageDriver {
            public function store(TraceContext $trace): void
            {
                throw new Exception('storage down');
            }

            public function prune(DateTimeInterface $before): int
            {
                return 0;
            }
        });

        $this->get('/ok')->assertOk()->assertSee('ok');
    }
}
