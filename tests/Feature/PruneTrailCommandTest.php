<?php

namespace Trail\Tests\Feature;

use Illuminate\Support\Carbon;
use Trail\Models\TrailTrace;
use Trail\Tests\TestCase;

class PruneTrailCommandTest extends TestCase
{
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    public function test_it_prunes_traces_older_than_retention_days(): void
    {
        TrailTrace::query()->create([
            'trace_id' => 'old',
            'created_at' => Carbon::now()->subDays(91),
            'updated_at' => Carbon::now()->subDays(91),
        ]);

        TrailTrace::query()->create([
            'trace_id' => 'new',
            'created_at' => Carbon::now()->subDays(10),
            'updated_at' => Carbon::now()->subDays(10),
        ]);

        $this->artisan('trail:prune')->assertExitCode(0);

        $this->assertFalse(TrailTrace::query()->where('trace_id', 'old')->exists());
        $this->assertTrue(TrailTrace::query()->where('trace_id', 'new')->exists());
    }
}
