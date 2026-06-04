<?php

namespace Trail\Storage;

use DateTimeInterface;
use Trail\TraceContext;

class NullTrailStorage implements TrailStorageDriver
{
    public function store(TraceContext $trace): void
    {
    }

    public function prune(DateTimeInterface $before): int
    {
        return 0;
    }
}
