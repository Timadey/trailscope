<?php

namespace Trail\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Trail\Models\TrailTrace;

class TraceShowController
{
    public function __invoke(TrailTrace $trace): Response
    {
        return Inertia::render('Traces/Show', [
            'trace' => $trace->load('steps'),
            'canViewTechnicalContext' => true,
        ]);
    }
}
