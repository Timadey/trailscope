<?php

namespace Trail\Commands;

use Illuminate\Console\Command;
use Trail\Storage\TrailStorageDriver;

class PruneTrailCommand extends Command
{
    protected $signature = 'trail:prune';

    protected $description = 'Delete Trail traces older than configured retention.';

    public function handle(TrailStorageDriver $storage): int
    {
        $days = (int) config('trail.retention.days', 90);
        $deleted = $storage->prune(now()->subDays($days));

        $this->info("Pruned {$deleted} Trail traces.");

        return self::SUCCESS;
    }
}
