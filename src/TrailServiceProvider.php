<?php

namespace Trail;

use Illuminate\Support\ServiceProvider;

class TrailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/trail.php', 'trail');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/trail.php' => config_path('trail.php'),
        ], 'trail-config');
    }
}
