<?php

namespace Trail\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Trail\Models\TrailTrace;

class UserJourneyController
{
    public function __invoke(string $ownerType, string $ownerId): Response
    {
        return Inertia::render('Journeys/User', [
            'ownerType' => $ownerType,
            'ownerId' => $ownerId,
            'traces' => TrailTrace::query()
                ->where('owner_type', $ownerType)
                ->where('owner_id', $ownerId)
                ->oldest('started_at')
                ->get(),
        ]);
    }
}
