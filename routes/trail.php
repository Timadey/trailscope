<?php

use Illuminate\Support\Facades\Route;
use Trail\Http\Controllers\AuthController;
use Trail\Http\Controllers\TraceIndexController;
use Trail\Http\Controllers\TraceShowController;
use Trail\Http\Controllers\UserJourneyController;
use Trail\Http\Middleware\AuthorizeTrailDashboard;

Route::middleware(config('trail.middleware', ['web']))
    ->prefix(config('trail.path', 'trail'))
    ->group(function () {
        Route::get('/assets/{asset}', function (string $asset) {
            $path = realpath(__DIR__ . '/../dist/assets/' . basename($asset));
            $assetRoot = realpath(__DIR__ . '/../dist/assets');

            abort_unless($path && $assetRoot && str_starts_with($path, $assetRoot), 404);

            $contentType = match (true) {
                str_ends_with($asset, '.js') => 'application/javascript',
                str_ends_with($asset, '.css') => 'text/css; charset=UTF-8',
                default => 'application/octet-stream',
            };

            return response()->file($path, ['Content-Type' => $contentType]);
        })->where('asset', '[A-Za-z0-9_.-]+')->name('trail.assets');

        Route::get('/login', [AuthController::class, 'login'])->name('trail.login');
        Route::post('/login', [AuthController::class, 'authenticate'])->name('trail.authenticate');
        Route::post('/logout', [AuthController::class, 'logout'])->name('trail.logout');
    });

Route::middleware(array_merge(config('trail.middleware', ['web']), [AuthorizeTrailDashboard::class]))
    ->prefix(config('trail.path', 'trail'))
    ->group(function () {
        Route::get('/traces', TraceIndexController::class)->name('trail.traces.index');
        Route::get('/traces/{trace}', TraceShowController::class)->name('trail.traces.show');
        Route::get('/journeys/{ownerType}/{ownerId}', UserJourneyController::class)->name('trail.journeys.user');
    });
