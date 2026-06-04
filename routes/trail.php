<?php

use Illuminate\Support\Facades\Route;
use Trail\Http\Controllers\TraceIndexController;
use Trail\Http\Controllers\TraceShowController;
use Trail\Http\Controllers\UserJourneyController;
use Trail\Http\Middleware\AuthorizeTrailDashboard;

Route::middleware(array_merge(config('trail.middleware', ['web']), [AuthorizeTrailDashboard::class]))
    ->prefix(config('trail.path', 'trail'))
    ->group(function () {
        Route::get('/traces', TraceIndexController::class)->name('trail.traces.index');
        Route::get('/traces/{trace}', TraceShowController::class)->name('trail.traces.show');
        Route::get('/journeys/{ownerType}/{ownerId}', UserJourneyController::class)->name('trail.journeys.user');
    });
