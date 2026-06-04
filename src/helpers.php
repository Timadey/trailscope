<?php

use Trail\TrailManager;

if (! function_exists('step')) {
    function step(string $message, mixed ...$context): void
    {
        if (app()->bound(TrailManager::class)) {
            app(TrailManager::class)->step($message, ...$context);
        }
    }
}
