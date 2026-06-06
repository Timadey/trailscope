<?php

namespace Trail\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Trail\Models\TrailUser;
use Trail\Models\TrailTrace;

class TraceShowController
{
    public function __invoke(TrailTrace $trace): Response
    {
        $trace->load('steps');

        return Inertia::render('Traces/Show', [
            'trace' => array_merge($trace->toArray(), [
                'journey_url' => $this->journeyUrl($trace),
            ]),
            'canViewTechnicalContext' => $this->canViewTechnicalContext(),
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

    private function canViewTechnicalContext(): bool
    {
        if (config('trail.access.mode') !== 'trail_users') {
            return true;
        }

        $user = TrailUser::query()->find(session('trail_user_id'));

        return (bool) $user?->canViewTechnicalContext();
    }
}
