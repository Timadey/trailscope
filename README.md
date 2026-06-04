# Trail

Trail is a Laravel package for request traces and user journey observability.

## Install

```bash
composer require trail/trail
php artisan vendor:publish --tag=trail-config
php artisan vendor:publish --tag=trail-migrations
php artisan migrate
php artisan trail:user admin@example.com --name="Admin" --role=admin
```

## Capture Requests

Add the middleware to routes that should be traced:

```php
Route::middleware([\Trail\Http\Middleware\RecordTrail::class])->group(function () {
    Route::post('/transfer', TransferController::class);
});
```

## Add Developer Steps

```php
step('charging wallet', $wallet, $amount, $response);
```

Trail automatically attaches the step to the active request trace, normalizes context, sanitizes sensitive data, resolves identity, and stores the trace through the configured driver.

## Storage

The default storage driver is the host database. Redis is also available:

```env
TRAIL_STORAGE_DRIVER=redis
TRAIL_REDIS_CONNECTION=default
TRAIL_REDIS_PREFIX=trail
TRAIL_REDIS_TTL_DAYS=90
```
