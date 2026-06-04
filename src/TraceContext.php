<?php

namespace Trail;

use Trail\Identity\ResolvedIdentity;

class TraceContext
{
    public array $steps = [];

    public function __construct(
        public string $traceId,
        public array $attributes = [],
        public ?ResolvedIdentity $identity = null,
    ) {
    }

    public function addStep(string $message, array $context = []): void
    {
        $this->steps[] = [
            'message' => $message,
            'context' => $context,
            'recorded_at' => now(),
        ];
    }

    public function finish(array $attributes = []): void
    {
        $this->attributes = array_merge($this->attributes, $attributes);
    }
}
