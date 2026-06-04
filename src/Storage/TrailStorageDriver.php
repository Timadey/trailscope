<?php

namespace Trail\Storage;

use DateTimeInterface;
use Trail\TraceContext;

interface TrailStorageDriver
{
    public function store(TraceContext $trace): void;

    public function prune(DateTimeInterface $before): int;
}
