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
            ->simplePaginate(25)
            ->through(fn (TrailTrace $trace) => array_merge($trace->toArray(), [
                'url' => route('trail.traces.show', $trace),
                'journey_url' => $this->journeyUrl($trace),
            ]));

        return Inertia::render('Traces/Index', [
            'traces' => $traces,
            'logoutUrl' => route('trail.logout'),
        ]);
    }

    private function journeyUrl(TrailTrace $trace): ?string
    {
        if (! $trace->owner_type || ! $trace->owner_id) {
            return null;
        }

        return route('trail.journeys.user', [$trace->owner_type, $trace->owner_id]);
    }
}
