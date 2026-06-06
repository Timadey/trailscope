<?php

namespace Trail\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Trail\Models\TrailTrace;

class TraceIndexController
{
    public function __invoke(): Response
    {
        $traces = TrailTrace::query()
            ->latest('started_at')
            ->limit(100)
            ->get()
            ->map(fn (TrailTrace $trace) => array_merge($trace->toArray(), [
                'url' => route('trail.traces.show', $trace),
            ]));

        return Inertia::render('Traces/Index', [
            'traces' => $traces,
        ]);
    }
}
