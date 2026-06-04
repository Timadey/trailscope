<?php

return [
    'enabled' => env('TRAIL_ENABLED', true),
    'path' => env('TRAIL_PATH', 'trail'),
    'middleware' => ['web'],

    'access' => [
        'mode' => env('TRAIL_ACCESS_MODE', 'trail_users'),
        'gate' => env('TRAIL_GATE', 'viewTrail'),
        'ip_allowlist' => array_filter(explode(',', env('TRAIL_IP_ALLOWLIST', ''))),
        'signed_url_ttl_minutes' => (int) env('TRAIL_SIGNED_URL_TTL_MINUTES', 60),
    ],

    'storage' => [
        'driver' => env('TRAIL_STORAGE_DRIVER', 'database'),
        'write_mode' => env('TRAIL_WRITE_MODE', 'after_response'),
        'database' => [
            'connection' => env('TRAIL_DB_CONNECTION'),
        ],
        'redis' => [
            'connection' => env('TRAIL_REDIS_CONNECTION', 'default'),
            'prefix' => env('TRAIL_REDIS_PREFIX', 'trail'),
            'ttl_days' => (int) env('TRAIL_REDIS_TTL_DAYS', 90),
            'driver' => 'redis',
        ],
    ],

    'retention' => [
        'days' => (int) env('TRAIL_RETENTION_DAYS', 90),
    ],

    'capture' => [
        'headers' => false,
        'ip' => true,
        'user_agent' => true,
        'max_context_bytes' => 65536,
        'max_steps_per_trace' => 200,
        'response_preview_bytes' => 8192,
        'sample_success_rate' => 1.0,
    ],

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

    'logging' => [
        'mirror_to_app_log' => false,
    ],
];
