<?php

namespace Trail\Storage;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redis;
use Trail\TraceContext;

class RedisTrailStorage implements TrailStorageDriver
{
    public function __construct(
        private string $connection,
        private string $prefix,
        private int $ttlDays,
    ) {
    }

    public function store(TraceContext $trace): void
    {
        $client = Redis::connection($this->connection);
        $timestamp = $this->timestamp($trace->attributes['started_at'] ?? now());
        $ttl = max($this->ttlDays, 1) * 86400;
        $payload = json_encode($this->payload($trace), JSON_THROW_ON_ERROR);

        $client->setex($this->key("traces:{$trace->traceId}"), $ttl, $payload);
        $client->zadd($this->key('indexes:traces'), $timestamp, $trace->traceId);

        if ($trace->identity?->ownerType && $trace->identity?->ownerId) {
            $client->zadd(
                $this->key("indexes:owners:{$trace->identity->ownerType}:{$trace->identity->ownerId}"),
                $timestamp,
                $trace->traceId,
            );
        }
    }

    public function prune(DateTimeInterface $before): int
    {
        $client = Redis::connection($this->connection);

        return (int) $client->zremrangebyscore($this->key('indexes:traces'), '-inf', $before->getTimestamp());
    }

    private function payload(TraceContext $trace): array
    {
        return [
            'trace_id' => $trace->traceId,
            'attributes' => $trace->attributes,
            'identity' => $trace->identity?->toArray(),
            'steps' => $trace->steps,
        ];
    }

    private function key(string $suffix): string
    {
        return trim($this->prefix, ':') . ':' . $suffix;
    }

    private function timestamp(mixed $value): int
    {
        if ($value instanceof DateTimeInterface) {
            return $value->getTimestamp();
        }

        return Carbon::parse($value)->getTimestamp();
    }
}
