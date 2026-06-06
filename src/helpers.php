<?php

use Trail\TrailManager;
use Trail\Support\StepContextKeys;

if (! function_exists('step')) {
    function step(string $message, mixed ...$context): void
    {
        if (app()->bound(TrailManager::class)) {
            app(TrailManager::class)->stepWithKeys($message, StepContextKeys::fromCaller(), ...$context);
        }
    }
}
