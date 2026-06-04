<?php

namespace Trail\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Trail\Context\ContextNormalizer;
use Trail\Identity\IdentityResolver;
use Trail\Jobs\FlushTrailTrace;
use Trail\Storage\TrailStorageDriver;
use Trail\TrailManager;
use Trail\TraceContext;

class RecordTrail
{
    public function __construct(
        private TrailManager $trail,
        private ContextNormalizer $normalizer,
        private IdentityResolver $identityResolver,
        private TrailStorageDriver $storage,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('trail.enabled', true)) {
            return $next($request);
        }

        $startedAt = microtime(true);
        $trace = $this->trail->start([
            'method' => $request->method(),
            'path' => $request->path(),
            'route_name' => optional($request->route())->getName(),
            'controller' => $this->controller($request),
            'request' => $this->normalizer->normalize([$request])['request_1'] ?? [],
            'started_at' => now(),
        ]);

        $trace->addStep('entered request');

        try {
            $response = $next($request);
        } catch (Throwable $exception) {
            $trace->identity = $this->identityResolver->resolve($request, $trace);
            $trace->finish([
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'exception' => $this->normalizer->normalize([$exception], true)['exception_1'] ?? [],
                'ended_at' => now(),
            ]);
            $trace->addStep('left request with exception');
            $this->flush($trace);
            $this->trail->clear();

            throw $exception;
        }

        $trace->identity = $this->identityResolver->resolve($request, $trace);
        $trace->finish([
            'status_code' => $response->getStatusCode(),
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'response' => $this->normalizer->normalize([$response])['response_1'] ?? [],
            'ended_at' => now(),
        ]);
        $trace->addStep('left request');
        $this->flush($trace);
        $this->trail->clear();

        return $response;
    }

    private function controller(Request $request): ?string
    {
        $action = $request->route()?->getActionName();

        return $action === 'Closure' ? null : $action;
    }

    private function flush(TraceContext $trace): void
    {
        try {
            match (config('trail.storage.write_mode', 'after_response')) {
                'queue' => FlushTrailTrace::dispatch($trace),
                default => $this->storage->store($trace),
            };
        } catch (Throwable $storageException) {
            report($storageException);
        }
    }
}
