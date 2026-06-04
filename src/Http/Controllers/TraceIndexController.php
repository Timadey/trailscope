<?php

namespace Trail\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Trail\Models\TrailTrace;

class TraceIndexController
{
    public function __invoke(): Response
    {
        return Inertia::render('Traces/Index', [
            'traces' => TrailTrace::query()
                ->latest('started_at')
                ->limit(100)
                ->get(),
        ]);
    }
}
