<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Capture Switch
    |--------------------------------------------------------------------------
    |
    | Turn TrailScope recording on or off without removing middleware from your
    | routes. This is useful for local debugging, staging rollouts, or temporarily
    | disabling capture in production.
    |
    | Example: TRAIL_ENABLED=false
    |
    */
    'enabled' => env('TRAIL_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Dashboard Path
    |--------------------------------------------------------------------------
    |
    | The URI prefix where the TrailScope dashboard is mounted. The default value
    | exposes pages such as /trail/traces and /trail/login.
    |
    | Example: TRAIL_PATH=internal/trailscope
    |
    */
    'path' => env('TRAIL_PATH', 'trail'),

    /*
    |--------------------------------------------------------------------------
    | Dashboard Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware applied to TrailScope dashboard routes. Keep "web" enabled if
    | you use sessions, cookies, CSRF protection, or TrailScope users.
    |
    */
    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Access
    |--------------------------------------------------------------------------
    |
    | Choose how dashboard requests are authorized.
    |
    | Supported: trail_users, gate, signed_url.
    |
    | trail_users: use TrailScope's built-in dashboard users.
    | gate: defer access to a Laravel Gate, configured by access.gate.
    | signed_url: allow access through temporary signed dashboard links.
    |
    | ip_allowlist is optional perimeter protection. Leave it empty to allow all
    | client IPs through to the selected access mode.
    |
    | Example: TRAIL_ACCESS_MODE=gate
    | Example: TRAIL_GATE=viewTrail
    | Example: TRAIL_IP_ALLOWLIST=127.0.0.1,10.0.0.5
    |
    */
    'access' => [
        'mode' => env('TRAIL_ACCESS_MODE', 'trail_users'),
        'gate' => env('TRAIL_GATE', 'viewTrail'),
        'ip_allowlist' => array_filter(explode(',', env('TRAIL_IP_ALLOWLIST', ''))),
        'signed_url_ttl_minutes' => (int) env('TRAIL_SIGNED_URL_TTL_MINUTES', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    |
    | Select where traces are stored and when writes happen.
    |
    | Supported storage drivers: database, redis.
    | Supported: sync, after_response, queue.
    |
    | sync: write during the request before the response is returned.
    | after_response: write after Laravel sends the response to the client.
    | queue: dispatch a job so your queue worker stores the trace.
    |
    | Use Redis when you prefer TTL-based trace retention or want to reduce writes
    | to your application database. The Redis prefix keeps TrailScope keys grouped.
    |
    | Example: TRAIL_STORAGE_DRIVER=redis
    | Example: TRAIL_WRITE_MODE=queue
    | Example: TRAIL_REDIS_PREFIX=trail
    |
    */
    'storage' => [
        'driver' => env('TRAIL_STORAGE_DRIVER', 'database'),
        'write_mode' => env('TRAIL_WRITE_MODE', 'after_response'),
        'database' => [
            /*
            | Reserved for selecting a database connection for stored traces.
            | Null means the application's default model connection is used.
            */
            'connection' => env('TRAIL_DB_CONNECTION'),
        ],
        'redis' => [
            /*
            | Redis connection, key prefix, and TTL for Redis-backed traces.
            |
            | Example: TRAIL_REDIS_CONNECTION=default
            | Example: TRAIL_REDIS_TTL_DAYS=90
            */
            'connection' => env('TRAIL_REDIS_CONNECTION', 'default'),
            'prefix' => env('TRAIL_REDIS_PREFIX', 'trail'),
            'ttl_days' => (int) env('TRAIL_REDIS_TTL_DAYS', 90),
            'driver' => 'redis',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Retention
    |--------------------------------------------------------------------------
    |
    | Number of days to keep traces when pruning old records. For database
    | storage, run the trail:prune command on a schedule. Redis storage also uses
    | storage.redis.ttl_days for automatic key expiry.
    |
    | Example: TRAIL_RETENTION_DAYS=30
    |
    */
    'retention' => [
        'days' => (int) env('TRAIL_RETENTION_DAYS', 90),
    ],

    /*
    |--------------------------------------------------------------------------
    | Capture Detail
    |--------------------------------------------------------------------------
    |
    | Control how much request, response, and step context TrailScope stores.
    | Lower limits reduce storage usage and help avoid keeping large payloads.
    |
    | headers: include request headers in captured request context.
    | except_paths: request path patterns that should not be traced.
    | except_route_names: route-name patterns that should not be traced.
    | ip: include the request IP address.
    | user_agent: include the browser or client user agent.
    | max_context_bytes: intended maximum normalized context size per step or payload.
    | max_steps_per_trace: intended maximum developer steps kept for a single trace.
    | response_preview_bytes: maximum response body preview size.
    | sample_success_rate: intended fraction of successful requests to keep, from 0.0
    | to 1.0.
    |
    | Example: set sample_success_rate to 0.25 when sampling support is enabled.
    | Example except_paths: health, up, horizon/*, telescope/*
    |
    */
    'capture' => [
        'except_paths' => [
            'health',
            'up',
        ],
        'except_route_names' => [],
        'headers' => false,
        'ip' => true,
        'user_agent' => true,
        'max_context_bytes' => 65536,
        'max_steps_per_trace' => 200,
        'response_preview_bytes' => 8192,
        'sample_success_rate' => 1.0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Step Context
    |--------------------------------------------------------------------------
    |
    | When enabled, TrailScope tries to infer simple positional variable names in
    | calls such as step('checking network', $product, $is_active). PHP does not
    | expose variable names at runtime, so this is best-effort source inspection.
    | Named arguments and associative arrays remain the most reliable option.
    |
    */
    'steps' => [
        'infer_variable_names' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Sanitization
    |--------------------------------------------------------------------------
    |
    | Sensitive keys are masked before context is stored. Add domain-specific
    | fields here for payments, health data, identity documents, or secrets.
    |
    | Example sensitive keys: password, token, authorization, otp, card.
    |
    */
    'sanitization' => [
        'sensitive_keys' => [
            'password',
            'pin',
            'token',
            'authorization',
            'signature',
            'secret',
            'bvn',
            'nin',
            'otp',
            'card',
            'account_number',
        ],
        'mask' => '[Filtered]',
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Reserved for mirroring captured steps to the application log when you want
    | local visibility while developing. Keep this off in production unless you are
    | comfortable with the additional log volume and sanitized context.
    |
    */
    'logging' => [
        'mirror_to_app_log' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Identity Resolution
    |--------------------------------------------------------------------------
    |
    | TrailScope uses these request payload keys to connect traces into a user or
    | owner journey when no authenticated user resolver provides an identity.
    | Add identifiers that make sense in your app, such as customer_id, tenant_id,
    | merchant_id, transaction_reference, or account_id.
    |
    */
    'identity' => [
        'payload_keys' => [
            'user_id',
            'wallet_id',
            'account_number',
            'phone',
            'phone_number',
            'email',
            'email_address',
            'username',
            'reference',
            'bvn',
            'nin',
        ],
    ],
];
