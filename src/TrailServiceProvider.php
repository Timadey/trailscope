<?php

namespace Trail;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Inertia\Inertia;
use Trail\Http\Middleware\RecordTrail;
use Trail\Commands\CreateSignedTrailLinkCommand;
use Trail\Commands\CreateTrailUserCommand;
use Trail\Commands\PruneTrailCommand;
use Trail\Context\ContextNormalizer;
use Trail\Identity\IdentityResolver;
use Trail\Identity\Resolvers\AuthUserIdentityResolver;
use Trail\Identity\Resolvers\RequestPayloadIdentityResolver;
use Trail\Storage\DatabaseTrailStorage;
use Trail\Storage\NullTrailStorage;
use Trail\Storage\RedisTrailStorage;
use Trail\Storage\TrailStorageDriver;
use Trail\Support\Sanitizer;

class TrailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/trail.php', 'trail');

        $this->app->singleton(Sanitizer::class, function () {
            return new Sanitizer(
                config('trail.sanitization.sensitive_keys', []),
                config('trail.sanitization.mask', '[Filtered]'),
                config('trail.capture.response_preview_bytes', 8192),
            );
        });

        $this->app->singleton(ContextNormalizer::class);

        $this->app->singleton(IdentityResolver::class, function () {
            return new IdentityResolver([
                new AuthUserIdentityResolver(),
                new RequestPayloadIdentityResolver(),
            ]);
        });

        $this->app->bind(TrailStorageDriver::class, function () {
            return match (config('trail.storage.driver', 'database')) {
                'database' => new DatabaseTrailStorage(),
                'redis' => new RedisTrailStorage(
                    config('trail.storage.redis.connection', 'default'),
                    config('trail.storage.redis.prefix', 'trail'),
                    config('trail.storage.redis.ttl_days', 90),
                ),
                default => new NullTrailStorage(),
            };
        });

        $this->app->scoped(TrailManager::class);
    }

    public function boot(): void
    {
        $this->app['router']->aliasMiddleware('trail', RecordTrail::class);

        $this->publishes([
            __DIR__ . '/../config/trail.php' => config_path('trail.php'),
        ], 'trail-config');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'trail-migrations');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes/trail.php');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'trail');

        View::composer('trail::app', function ($view) {
            $manifest = json_decode(file_get_contents(__DIR__ . '/../dist/.vite/manifest.json'), true, flags: JSON_THROW_ON_ERROR);
            $entry = $manifest['resources/js/app.tsx'];

            $view->with('trailScript', basename($entry['file']));
            $view->with('trailStyles', array_map('basename', $entry['css'] ?? []));
        });

        Inertia::setRootView('trail::app');

        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateTrailUserCommand::class,
                CreateSignedTrailLinkCommand::class,
                PruneTrailCommand::class,
            ]);
        }
    }
}
