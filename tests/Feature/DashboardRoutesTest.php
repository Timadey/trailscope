<?php

namespace Trail\Tests\Feature;

use Trail\Models\TrailTrace;
use Trail\Tests\TestCase;

class DashboardRoutesTest extends TestCase
{
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    public function test_trace_index_route_loads(): void
    {
        config(['trail.access.mode' => 'gate']);
        \Illuminate\Support\Facades\Gate::define('viewTrail', fn () => true);

        TrailTrace::query()->create(['trace_id' => 'trace-1', 'method' => 'GET', 'path' => 'test']);

        $this->get('/trail/traces')->assertOk();
    }
}
