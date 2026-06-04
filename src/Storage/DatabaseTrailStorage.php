<?php

namespace Trail\Storage;

use DateTimeInterface;
use Trail\Models\TrailTrace;
use Trail\TraceContext;

class DatabaseTrailStorage implements TrailStorageDriver
{
    public function store(TraceContext $trace): void
    {
        $identity = $trace->identity?->toArray() ?? [];

        $storedTrace = TrailTrace::query()->updateOrCreate(
            ['trace_id' => $trace->traceId],
            [
                'method' => $trace->attributes['method'] ?? null,
                'path' => $trace->attributes['path'] ?? null,
                'route_name' => $trace->attributes['route_name'] ?? null,
                'controller' => $trace->attributes['controller'] ?? null,
                'status_code' => $trace->attributes['status_code'] ?? null,
                'duration_ms' => $trace->attributes['duration_ms'] ?? null,
                'owner_type' => $identity['owner_type'] ?? null,
                'owner_id' => $identity['owner_id'] ?? null,
                'owner_label' => $identity['owner_label'] ?? null,
                'identity_source' => $identity['source'] ?? null,
                'identity_confidence' => $identity['confidence'] ?? null,
                'request' => $trace->attributes['request'] ?? null,
                'response' => $trace->attributes['response'] ?? null,
                'exception' => $trace->attributes['exception'] ?? null,
                'started_at' => $trace->attributes['started_at'] ?? now(),
                'ended_at' => $trace->attributes['ended_at'] ?? now(),
            ],
        );

        $storedTrace->steps()->delete();

        foreach ($trace->steps as $step) {
            $storedTrace->steps()->create([
                'message' => $step['message'],
                'context' => $step['context'] ?? [],
                'recorded_at' => $step['recorded_at'] ?? now(),
            ]);
        }
    }

    public function prune(DateTimeInterface $before): int
    {
        return TrailTrace::query()->where('created_at', '<', $before)->delete();
    }
}
