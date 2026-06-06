<?php

namespace Trail\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Trail\Models\TrailTrace;

class UserJourneyController
{
    public function __invoke(string $ownerType, string $ownerId): Response
    {
        $traces = TrailTrace::query()
            ->where('owner_type', $ownerType)
            ->where('owner_id', $ownerId)
            ->oldest('started_at')
            ->get()
            ->map(fn (TrailTrace $trace) => array_merge($trace->toArray(), [
                'url' => route('trail.traces.show', $trace),
            ]));

        return Inertia::render('Journeys/User', [
            'ownerType' => $ownerType,
            'ownerId' => $ownerId,
            'traces' => $traces,
            'logoutUrl' => route('trail.logout'),
            'tracesUrl' => route('trail.traces.index'),
        ]);
    }
}
