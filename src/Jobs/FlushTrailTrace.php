<?php

namespace Trail\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Trail\Storage\TrailStorageDriver;
use Trail\TraceContext;

class FlushTrailTrace implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private TraceContext $trace)
    {
    }

    public function handle(TrailStorageDriver $storage): void
    {
        $storage->store($this->trace);
    }
}
