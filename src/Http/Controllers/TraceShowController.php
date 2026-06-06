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
        return Inertia::render('Traces/Show', [
            'trace' => $trace->load('steps'),
            'canViewTechnicalContext' => $this->canViewTechnicalContext(),
            'logoutUrl' => route('trail.logout'),
        ]);
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
