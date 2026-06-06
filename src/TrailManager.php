<?php

namespace Trail;

use Trail\Context\ContextNormalizer;
use Trail\Support\TraceId;

class TrailManager
{
    private ?TraceContext $current = null;

    public function __construct(private ContextNormalizer $normalizer)
    {
    }

    public function start(array $attributes = []): TraceContext
    {
        $this->current = new TraceContext(TraceId::make(), $attributes);

        return $this->current;
    }

    public function current(): ?TraceContext
    {
        return $this->current;
    }

    public function step(string $message, mixed ...$context): void
    {
        $this->stepWithKeys($message, [], ...$context);
    }

    public function stepWithKeys(string $message, array $keys = [], mixed ...$context): void
    {
        if (! $this->current) {
            return;
        }

        $this->current->addStep($message, $this->normalizer->normalize($context, false, $keys));
    }

    public function clear(): void
    {
        $this->current = null;
    }
}
