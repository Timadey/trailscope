# Trail Package Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a standalone Laravel package that automatically traces HTTP requests, records developer `step(...)` breadcrumbs, resolves trace ownership, and exposes an Inertia + React dashboard for support and developer investigation.

**Architecture:** The package is organized around a small runtime core: middleware starts and finishes traces, `TrailManager` owns active trace state, `ContextNormalizer` sanitizes developer context, `IdentityResolver` attributes traces to users/wallets/references, and storage drivers persist data safely. The dashboard is package-owned, role-aware, and served through Laravel routes with Inertia + React assets.

**Tech Stack:** Laravel package, PHP 8.1+, Orchestra Testbench, PHPUnit, Laravel migrations/config/routes, Inertia, React, Vite, Eloquent database driver, queue/after-response write modes.

---

## File Structure

- `composer.json`: package metadata, dependencies, autoload, test scripts.
- `config/trail.php`: published package configuration.
- `src/TrailServiceProvider.php`: registers config, routes, migrations, middleware aliases, helpers, and bindings.
- `src/helpers.php`: defines global `step(...)`.
- `src/TrailManager.php`: public runtime API used by helper and middleware.
- `src/TraceContext.php`: in-memory trace state for the active request.
- `src/Support/TraceId.php`: trace ID generation.
- `src/Support/Sanitizer.php`: masks, hashes, drops, and truncates sensitive values.
- `src/Context/ContextNormalizer.php`: turns mixed context arguments into safe arrays.
- `src/Context/Normalizers/*.php`: focused normalizers for models, exceptions, requests, responses, arrays, scalars, and objects.
- `src/Identity/IdentityResolver.php`: runs identity resolvers and returns best match.
- `src/Identity/ResolvedIdentity.php`: value object for identity attribution.
- `src/Identity/Resolvers/*.php`: auth guard, route model, request payload, and step context resolvers.
- `src/Http/Middleware/RecordTrail.php`: starts/finalizes HTTP traces.
- `src/Http/Middleware/AuthorizeTrailDashboard.php`: enforces access mode and IP allowlist.
- `src/Http/Controllers/*.php`: dashboard and auth controllers.
- `src/Models/TrailUser.php`: package dashboard user model.
- `src/Models/TrailTrace.php`: database trace model.
- `src/Models/TrailStep.php`: database step model.
- `src/Models/TrailSignedLink.php`: temporary access model.
- `src/Storage/TrailStorageDriver.php`: storage contract.
- `src/Storage/DatabaseTrailStorage.php`: default database implementation.
- `src/Storage/NullTrailStorage.php`: safe fallback/no-op implementation.
- `src/Jobs/FlushTrailTrace.php`: queued write mode.
- `src/Commands/PruneTrailCommand.php`: retention cleanup.
- `src/Commands/CreateTrailUserCommand.php`: creates package dashboard users.
- `src/Commands/CreateSignedTrailLinkCommand.php`: creates temporary signed links.
- `database/migrations/*.php.stub`: publishable migrations.
- `routes/trail.php`: dashboard routes.
- `resources/js/Pages/*.tsx`: Inertia React pages.
- `resources/js/Components/*.tsx`: dashboard UI components.
- `resources/js/app.tsx`: package dashboard entry point.
- `resources/views/app.blade.php`: Inertia root view.
- `tests/TestCase.php`: package test bootstrap.
- `tests/Feature/*.php`: HTTP, storage, access, command, and dashboard tests.
- `tests/Unit/*.php`: normalizer, sanitizer, identity, and manager tests.

---

### Task 1: Package Skeleton

**Files:**
- Create: `composer.json`
- Create: `phpunit.xml.dist`
- Create: `src/TrailServiceProvider.php`
- Create: `src/helpers.php`
- Create: `tests/TestCase.php`
- Create: `tests/Feature/PackageBootTest.php`

- [ ] **Step 1: Create package metadata**

Create `composer.json`:

```json
{
  "name": "trail/trail",
  "description": "Laravel request and user journey observability package.",
  "type": "library",
  "license": "MIT",
  "require": {
    "php": "^8.1",
    "illuminate/support": "^10.0|^11.0|^12.0",
    "illuminate/routing": "^10.0|^11.0|^12.0",
    "illuminate/database": "^10.0|^11.0|^12.0",
    "inertiajs/inertia-laravel": "^1.0"
  },
  "require-dev": {
    "orchestra/testbench": "^8.0|^9.0|^10.0",
    "phpunit/phpunit": "^10.0|^11.0"
  },
  "autoload": {
    "psr-4": {
      "Trail\\": "src/"
    },
    "files": [
      "src/helpers.php"
    ]
  },
  "autoload-dev": {
    "psr-4": {
      "Trail\\Tests\\": "tests/"
    }
  },
  "extra": {
    "laravel": {
      "providers": [
        "Trail\\TrailServiceProvider"
      ]
    }
  },
  "scripts": {
    "test": "phpunit"
  },
  "minimum-stability": "stable",
  "prefer-stable": true
}
```

- [ ] **Step 2: Create PHPUnit config**

Create `phpunit.xml.dist`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="vendor/autoload.php" colors="true">
    <testsuites>
        <testsuite name="Package">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="TRAIL_ENABLED" value="true"/>
    </php>
</phpunit>
```

- [ ] **Step 3: Create minimal service provider**

Create `src/TrailServiceProvider.php`:

```php
<?php

namespace Trail;

use Illuminate\Support\ServiceProvider;

class TrailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
    }
}
```

- [ ] **Step 4: Create helper file**

Create `src/helpers.php`:

```php
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
```

- [ ] **Step 5: Create Testbench base**

Create `tests/TestCase.php`:

```php
<?php

namespace Trail\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Trail\TrailServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            TrailServiceProvider::class,
        ];
    }
}
```

- [ ] **Step 6: Write boot test**

Create `tests/Feature/PackageBootTest.php`:

```php
<?php

namespace Trail\Tests\Feature;

use Trail\Tests\TestCase;

class PackageBootTest extends TestCase
{
    public function test_step_helper_exists(): void
    {
        $this->assertTrue(function_exists('step'));
    }
}
```

- [ ] **Step 7: Run package boot test**

Run: `composer test -- --filter PackageBootTest`

Expected: PASS with one test.

- [ ] **Step 8: Commit**

```bash
git add composer.json phpunit.xml.dist src tests
git commit -m "chore: scaffold trail package"
```

---

### Task 2: Configuration And Bindings

**Files:**
- Create: `config/trail.php`
- Modify: `src/TrailServiceProvider.php`
- Create: `tests/Feature/ConfigurationTest.php`

- [ ] **Step 1: Write configuration test**

Create `tests/Feature/ConfigurationTest.php`:

```php
<?php

namespace Trail\Tests\Feature;

use Trail\Tests\TestCase;

class ConfigurationTest extends TestCase
{
    public function test_trail_config_is_loaded(): void
    {
        $this->assertTrue(config('trail.enabled'));
        $this->assertSame(90, config('trail.retention.days'));
        $this->assertSame('trail_users', config('trail.access.mode'));
        $this->assertSame('database', config('trail.storage.driver'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `composer test -- --filter ConfigurationTest`

Expected: FAIL because `config('trail.enabled')` is not loaded.

- [ ] **Step 3: Create package config**

Create `config/trail.php`:

```php
<?php

return [
    'enabled' => env('TRAIL_ENABLED', true),
    'path' => env('TRAIL_PATH', 'trail'),
    'middleware' => ['web'],

    'access' => [
        'mode' => env('TRAIL_ACCESS_MODE', 'trail_users'),
        'gate' => env('TRAIL_GATE', 'viewTrail'),
        'ip_allowlist' => array_filter(explode(',', env('TRAIL_IP_ALLOWLIST', ''))),
        'signed_url_ttl_minutes' => env('TRAIL_SIGNED_URL_TTL_MINUTES', 60),
    ],

    'storage' => [
        'driver' => env('TRAIL_STORAGE_DRIVER', 'database'),
        'write_mode' => env('TRAIL_WRITE_MODE', 'after_response'),
        'database' => [
            'connection' => env('TRAIL_DB_CONNECTION'),
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
        ],
        'mask' => '[Filtered]',
    ],

    'logging' => [
        'mirror_to_app_log' => false,
    ],
];
```

- [ ] **Step 4: Register config in provider**

Modify `src/TrailServiceProvider.php`:

```php
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
```

- [ ] **Step 5: Run configuration test**

Run: `composer test -- --filter ConfigurationTest`

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add config src tests
git commit -m "feat: add trail configuration"
```

---

### Task 3: Trace Manager And Steps

**Files:**
- Create: `src/TrailManager.php`
- Create: `src/TraceContext.php`
- Create: `src/Support/TraceId.php`
- Modify: `src/TrailServiceProvider.php`
- Create: `tests/Unit/TrailManagerTest.php`

- [ ] **Step 1: Write manager tests**

Create `tests/Unit/TrailManagerTest.php`:

```php
<?php

namespace Trail\Tests\Unit;

use Trail\Tests\TestCase;
use Trail\TrailManager;

class TrailManagerTest extends TestCase
{
    public function test_it_records_steps_on_active_trace(): void
    {
        $manager = app(TrailManager::class);

        $trace = $manager->start(['method' => 'POST', 'path' => '/transfer']);
        $manager->step('checking wallet', 5000);

        $this->assertSame($trace, $manager->current());
        $this->assertCount(1, $trace->steps);
        $this->assertSame('checking wallet', $trace->steps[0]['message']);
        $this->assertSame([5000], $trace->steps[0]['context']);
    }

    public function test_step_without_active_trace_is_ignored(): void
    {
        $manager = app(TrailManager::class);

        $manager->step('outside request');

        $this->assertNull($manager->current());
    }
}
```

- [ ] **Step 2: Run tests to verify failure**

Run: `composer test -- --filter TrailManagerTest`

Expected: FAIL because `TrailManager` does not exist.

- [ ] **Step 3: Create trace ID generator**

Create `src/Support/TraceId.php`:

```php
<?php

namespace Trail\Support;

use Illuminate\Support\Str;

class TraceId
{
    public static function make(): string
    {
        return (string) Str::uuid();
    }
}
```

- [ ] **Step 4: Create trace context**

Create `src/TraceContext.php`:

```php
<?php

namespace Trail;

class TraceContext
{
    public array $steps = [];

    public function __construct(
        public string $traceId,
        public array $attributes = [],
    ) {
    }

    public function addStep(string $message, array $context = []): void
    {
        $this->steps[] = [
            'message' => $message,
            'context' => $context,
            'recorded_at' => now(),
        ];
    }
}
```

- [ ] **Step 5: Create manager**

Create `src/TrailManager.php`:

```php
<?php

namespace Trail;

use Trail\Support\TraceId;

class TrailManager
{
    private ?TraceContext $current = null;

    public function start(array $attributes = []): TraceContext
    {
        $this->current = new TraceContext(TraceId::make(), $attributes);

        return $this->current;
    }

    public function current(): ?TraceContext
    {
        return $this->current;
    }

    public function step(string $message, mixed ...$context): void
    {
        if (! $this->current) {
            return;
        }

        $this->current->addStep($message, $context);
    }

    public function clear(): void
    {
        $this->current = null;
    }
}
```

- [ ] **Step 6: Bind manager as scoped service**

Modify `src/TrailServiceProvider.php`:

```php
<?php

namespace Trail;

use Illuminate\Support\ServiceProvider;

class TrailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/trail.php', 'trail');
        $this->app->scoped(TrailManager::class, fn () => new TrailManager());
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/trail.php' => config_path('trail.php'),
        ], 'trail-config');
    }
}
```

- [ ] **Step 7: Run manager tests**

Run: `composer test -- --filter TrailManagerTest`

Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add src tests
git commit -m "feat: add trace manager and step recording"
```

---

### Task 4: Sanitizer And Context Normalizer

**Files:**
- Create: `src/Support/Sanitizer.php`
- Create: `src/Context/ContextNormalizer.php`
- Modify: `src/TrailManager.php`
- Modify: `src/TrailServiceProvider.php`
- Create: `tests/Unit/SanitizerTest.php`
- Create: `tests/Unit/ContextNormalizerTest.php`

- [ ] **Step 1: Write sanitizer tests**

Create `tests/Unit/SanitizerTest.php`:

```php
<?php

namespace Trail\Tests\Unit;

use Trail\Support\Sanitizer;
use Trail\Tests\TestCase;

class SanitizerTest extends TestCase
{
    public function test_it_masks_sensitive_keys_recursively(): void
    {
        $sanitizer = new Sanitizer(['password', 'token'], '[Filtered]');

        $result = $sanitizer->clean([
            'email' => 'user@example.test',
            'password' => 'secret',
            'meta' => ['token' => 'abc'],
        ]);

        $this->assertSame('user@example.test', $result['email']);
        $this->assertSame('[Filtered]', $result['password']);
        $this->assertSame('[Filtered]', $result['meta']['token']);
    }
}
```

- [ ] **Step 2: Write context normalizer tests**

Create `tests/Unit/ContextNormalizerTest.php`:

```php
<?php

namespace Trail\Tests\Unit;

use Exception;
use Trail\Context\ContextNormalizer;
use Trail\Support\Sanitizer;
use Trail\Tests\TestCase;

class ContextNormalizerTest extends TestCase
{
    public function test_it_normalizes_scalars_arrays_and_exceptions(): void
    {
        $normalizer = new ContextNormalizer(new Sanitizer(['password'], '[Filtered]'));

        $result = $normalizer->normalize([
            5000,
            ['password' => 'secret'],
            new Exception('Provider failed'),
        ]);

        $this->assertSame(5000, $result['value_1']);
        $this->assertSame('[Filtered]', $result['context_2']['password']);
        $this->assertSame(Exception::class, $result['exception_3']['class']);
        $this->assertSame('Provider failed', $result['exception_3']['message']);
    }
}
```

- [ ] **Step 3: Run tests to verify failure**

Run: `composer test -- --filter "SanitizerTest|ContextNormalizerTest"`

Expected: FAIL because sanitizer and normalizer do not exist.

- [ ] **Step 4: Create sanitizer**

Create `src/Support/Sanitizer.php`:

```php
<?php

namespace Trail\Support;

class Sanitizer
{
    public function __construct(
        private array $sensitiveKeys,
        private string $mask,
    ) {
    }

    public function clean(mixed $value): mixed
    {
        if (is_array($value)) {
            return $this->cleanArray($value);
        }

        if (is_string($value) && strlen($value) > 8192) {
            return substr($value, 0, 8192) . '...[truncated]';
        }

        return $value;
    }

    private function cleanArray(array $data): array
    {
        $clean = [];

        foreach ($data as $key => $value) {
            if ($this->isSensitiveKey((string) $key)) {
                $clean[$key] = $this->mask;
                continue;
            }

            $clean[$key] = $this->clean($value);
        }

        return $clean;
    }

    private function isSensitiveKey(string $key): bool
    {
        foreach ($this->sensitiveKeys as $sensitiveKey) {
            if (str_contains(strtolower($key), strtolower($sensitiveKey))) {
                return true;
            }
        }

        return false;
    }
}
```

- [ ] **Step 5: Create context normalizer**

Create `src/Context/ContextNormalizer.php`:

```php
<?php

namespace Trail\Context;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Trail\Support\Sanitizer;

class ContextNormalizer
{
    public function __construct(private Sanitizer $sanitizer)
    {
    }

    public function normalize(array $context): array
    {
        $normalized = [];

        foreach (array_values($context) as $index => $value) {
            $position = $index + 1;

            if ($value instanceof Throwable) {
                $normalized["exception_{$position}"] = [
                    'class' => $value::class,
                    'message' => $value->getMessage(),
                    'file' => $value->getFile(),
                    'line' => $value->getLine(),
                ];
                continue;
            }

            if ($value instanceof Model) {
                $normalized["model_{$position}"] = [
                    'class' => $value::class,
                    'id' => $value->getKey(),
                    'attributes' => $this->sanitizer->clean($value->only($value->getVisible() ?: $value->getFillable())),
                ];
                continue;
            }

            if ($value instanceof Request) {
                $normalized["request_{$position}"] = [
                    'method' => $value->method(),
                    'path' => $value->path(),
                    'input' => $this->sanitizer->clean($value->all()),
                ];
                continue;
            }

            if ($value instanceof Response) {
                $normalized["response_{$position}"] = [
                    'status' => $value->getStatusCode(),
                    'content' => $this->sanitizer->clean($value->getContent()),
                ];
                continue;
            }

            if (is_array($value)) {
                $normalized["context_{$position}"] = $this->sanitizer->clean($value);
                continue;
            }

            if (is_scalar($value) || $value === null) {
                $normalized["value_{$position}"] = $this->sanitizer->clean($value);
                continue;
            }

            $normalized["object_{$position}"] = [
                'class' => $value::class,
            ];
        }

        return $normalized;
    }
}
```

- [ ] **Step 6: Bind sanitizer and normalizer**

Modify `src/TrailServiceProvider.php` register method:

```php
public function register(): void
{
    $this->mergeConfigFrom(__DIR__ . '/../config/trail.php', 'trail');

    $this->app->singleton(\Trail\Support\Sanitizer::class, function () {
        return new \Trail\Support\Sanitizer(
            config('trail.sanitization.sensitive_keys', []),
            config('trail.sanitization.mask', '[Filtered]'),
        );
    });

    $this->app->singleton(\Trail\Context\ContextNormalizer::class);
    $this->app->scoped(TrailManager::class, fn ($app) => new TrailManager($app->make(\Trail\Context\ContextNormalizer::class)));
}
```

- [ ] **Step 7: Update manager to normalize step context**

Modify `src/TrailManager.php`:

```php
<?php

namespace Trail;

use Trail\Context\ContextNormalizer;
use Trail\Support\TraceId;

class TrailManager
{
    private ?TraceContext $current = null;

    public function __construct(private ContextNormalizer $normalizer)
    {
    }

    public function start(array $attributes = []): TraceContext
    {
        $this->current = new TraceContext(TraceId::make(), $attributes);

        return $this->current;
    }

    public function current(): ?TraceContext
    {
        return $this->current;
    }

    public function step(string $message, mixed ...$context): void
    {
        if (! $this->current) {
            return;
        }

        $this->current->addStep($message, $this->normalizer->normalize($context));
    }

    public function clear(): void
    {
        $this->current = null;
    }
}
```

- [ ] **Step 8: Run tests**

Run: `composer test -- --filter "SanitizerTest|ContextNormalizerTest|TrailManagerTest"`

Expected: PASS after updating `TrailManagerTest` expected context from `[5000]` to `['value_1' => 5000]`.

- [ ] **Step 9: Commit**

```bash
git add src tests
git commit -m "feat: normalize and sanitize step context"
```

---

### Task 5: Database Storage

**Files:**
- Create: `src/Storage/TrailStorageDriver.php`
- Create: `src/Storage/DatabaseTrailStorage.php`
- Create: `src/Models/TrailTrace.php`
- Create: `src/Models/TrailStep.php`
- Create: `database/migrations/0001_01_01_000001_create_trail_traces_table.php.stub`
- Create: `database/migrations/0001_01_01_000002_create_trail_steps_table.php.stub`
- Modify: `src/TrailServiceProvider.php`
- Create: `tests/Feature/DatabaseStorageTest.php`

- [ ] **Step 1: Write database storage test**

Create `tests/Feature/DatabaseStorageTest.php`:

```php
<?php

namespace Trail\Tests\Feature;

use Trail\Models\TrailTrace;
use Trail\Storage\TrailStorageDriver;
use Trail\Tests\TestCase;
use Trail\TrailManager;

class DatabaseStorageTest extends TestCase
{
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    public function test_database_driver_persists_trace_and_steps(): void
    {
        $manager = app(TrailManager::class);
        $trace = $manager->start(['method' => 'POST', 'path' => '/transfer']);
        $manager->step('checking wallet', 5000);

        app(TrailStorageDriver::class)->store($trace);

        $stored = TrailTrace::query()->first();

        $this->assertNotNull($stored);
        $this->assertSame($trace->traceId, $stored->trace_id);
        $this->assertSame('/transfer', $stored->path);
        $this->assertSame('checking wallet', $stored->steps()->first()->message);
    }
}
```

- [ ] **Step 2: Run test to verify failure**

Run: `composer test -- --filter DatabaseStorageTest`

Expected: FAIL because storage classes and migrations do not exist.

- [ ] **Step 3: Create migrations**

Create `database/migrations/0001_01_01_000001_create_trail_traces_table.php.stub`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trail_traces', function (Blueprint $table) {
            $table->id();
            $table->string('trace_id')->unique();
            $table->string('owner_type')->nullable()->index();
            $table->string('owner_id')->nullable()->index();
            $table->string('owner_label')->nullable()->index();
            $table->string('identity_source')->nullable();
            $table->string('identity_confidence')->nullable();
            $table->string('method')->nullable();
            $table->string('path')->nullable()->index();
            $table->string('route_name')->nullable()->index();
            $table->string('controller_action')->nullable();
            $table->unsignedSmallInteger('status_code')->nullable()->index();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->json('request')->nullable();
            $table->json('response')->nullable();
            $table->json('exception')->nullable();
            $table->timestamp('started_at')->nullable()->index();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trail_traces');
    }
};
```

Create `database/migrations/0001_01_01_000002_create_trail_steps_table.php.stub`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trail_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trail_trace_id')->constrained('trail_traces')->cascadeOnDelete();
            $table->unsignedInteger('sequence');
            $table->string('message');
            $table->json('context')->nullable();
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trail_steps');
    }
};
```

- [ ] **Step 4: Create storage contract and models**

Create `src/Storage/TrailStorageDriver.php`:

```php
<?php

namespace Trail\Storage;

use Trail\TraceContext;

interface TrailStorageDriver
{
    public function store(TraceContext $trace): void;
}
```

Create `src/Models/TrailTrace.php`:

```php
<?php

namespace Trail\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrailTrace extends Model
{
    protected $guarded = [];

    protected $casts = [
        'request' => 'array',
        'response' => 'array',
        'exception' => 'array',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(TrailStep::class);
    }
}
```

Create `src/Models/TrailStep.php`:

```php
<?php

namespace Trail\Models;

use Illuminate\Database\Eloquent\Model;

class TrailStep extends Model
{
    protected $guarded = [];

    protected $casts = [
        'context' => 'array',
        'recorded_at' => 'datetime',
    ];
}
```

- [ ] **Step 5: Create database storage**

Create `src/Storage/DatabaseTrailStorage.php`:

```php
<?php

namespace Trail\Storage;

use Trail\Models\TrailTrace;
use Trail\TraceContext;

class DatabaseTrailStorage implements TrailStorageDriver
{
    public function store(TraceContext $trace): void
    {
        $record = TrailTrace::query()->create([
            'trace_id' => $trace->traceId,
            'method' => $trace->attributes['method'] ?? null,
            'path' => $trace->attributes['path'] ?? null,
            'route_name' => $trace->attributes['route_name'] ?? null,
            'controller_action' => $trace->attributes['controller_action'] ?? null,
            'status_code' => $trace->attributes['status_code'] ?? null,
            'duration_ms' => $trace->attributes['duration_ms'] ?? null,
            'request' => $trace->attributes['request'] ?? null,
            'response' => $trace->attributes['response'] ?? null,
            'exception' => $trace->attributes['exception'] ?? null,
            'started_at' => $trace->attributes['started_at'] ?? null,
            'ended_at' => $trace->attributes['ended_at'] ?? null,
        ]);

        foreach ($trace->steps as $index => $step) {
            $record->steps()->create([
                'sequence' => $index + 1,
                'message' => $step['message'],
                'context' => $step['context'],
                'recorded_at' => $step['recorded_at'],
            ]);
        }
    }
}
```

- [ ] **Step 6: Register migrations and storage**

Modify `src/TrailServiceProvider.php` boot method:

```php
public function boot(): void
{
    $this->publishes([
        __DIR__ . '/../config/trail.php' => config_path('trail.php'),
    ], 'trail-config');

    $this->publishes([
        __DIR__ . '/../database/migrations' => database_path('migrations'),
    ], 'trail-migrations');

    $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
}
```

Add to register method:

```php
$this->app->bind(\Trail\Storage\TrailStorageDriver::class, \Trail\Storage\DatabaseTrailStorage::class);
```

- [ ] **Step 7: Run storage test**

Run: `composer test -- --filter DatabaseStorageTest`

Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add database src tests
git commit -m "feat: add database trail storage"
```

---

### Task 6: HTTP Middleware Capture

**Files:**
- Create: `src/Http/Middleware/RecordTrail.php`
- Modify: `src/TrailManager.php`
- Modify: `src/TraceContext.php`
- Modify: `src/TrailServiceProvider.php`
- Create: `tests/Feature/HttpTracingTest.php`

- [ ] **Step 1: Write HTTP tracing test**

Create `tests/Feature/HttpTracingTest.php`:

```php
<?php

namespace Trail\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Trail\Http\Middleware\RecordTrail;
use Trail\Models\TrailTrace;
use Trail\Tests\TestCase;

class HttpTracingTest extends TestCase
{
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    protected function defineRoutes($router): void
    {
        Route::middleware(RecordTrail::class)->post('/transfer', function () {
            step('inside transfer', 5000);

            return response()->json(['ok' => true]);
        })->name('transfer.store');
    }

    public function test_middleware_records_request_and_response(): void
    {
        $this->postJson('/transfer', ['amount' => 5000])->assertOk();

        $trace = TrailTrace::query()->first();

        $this->assertSame('POST', $trace->method);
        $this->assertSame('transfer', $trace->path);
        $this->assertSame('transfer.store', $trace->route_name);
        $this->assertSame(200, $trace->status_code);
        $this->assertSame('inside transfer', $trace->steps()->first()->message);
    }
}
```

- [ ] **Step 2: Run test to verify failure**

Run: `composer test -- --filter HttpTracingTest`

Expected: FAIL because middleware does not exist.

- [ ] **Step 3: Add trace finalization methods**

Modify `src/TraceContext.php`:

```php
<?php

namespace Trail;

class TraceContext
{
    public array $steps = [];

    public function __construct(
        public string $traceId,
        public array $attributes = [],
    ) {
    }

    public function addStep(string $message, array $context = []): void
    {
        $this->steps[] = [
            'message' => $message,
            'context' => $context,
            'recorded_at' => now(),
        ];
    }

    public function merge(array $attributes): void
    {
        $this->attributes = array_merge($this->attributes, $attributes);
    }
}
```

Modify `src/TrailManager.php`:

```php
public function finish(array $attributes = []): ?TraceContext
{
    if (! $this->current) {
        return null;
    }

    $this->current->merge($attributes);

    return $this->current;
}
```

- [ ] **Step 4: Create HTTP middleware**

Create `src/Http/Middleware/RecordTrail.php`:

```php
<?php

namespace Trail\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Throwable;
use Trail\Storage\TrailStorageDriver;
use Trail\TrailManager;

class RecordTrail
{
    public function __construct(
        private TrailManager $trail,
        private TrailStorageDriver $storage,
    ) {
    }

    public function handle(Request $request, Closure $next)
    {
        if (! config('trail.enabled')) {
            return $next($request);
        }

        $startedAt = microtime(true);

        $this->trail->start([
            'method' => $request->method(),
            'path' => $request->path(),
            'route_name' => optional($request->route())->getName(),
            'controller_action' => $request->route()?->getActionName(),
            'request' => ['input' => $request->except(config('trail.sanitization.sensitive_keys', []))],
            'started_at' => now(),
        ]);

        try {
            $response = $next($request);

            $trace = $this->trail->finish([
                'status_code' => $response->getStatusCode(),
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                'response' => ['status' => $response->getStatusCode()],
                'ended_at' => now(),
            ]);

            if ($trace) {
                $this->storage->store($trace);
            }

            return $response;
        } catch (Throwable $exception) {
            $trace = $this->trail->finish([
                'status_code' => 500,
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                'exception' => [
                    'class' => $exception::class,
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                ],
                'ended_at' => now(),
            ]);

            if ($trace) {
                $this->storage->store($trace);
            }

            throw $exception;
        } finally {
            $this->trail->clear();
        }
    }
}
```

- [ ] **Step 5: Register middleware alias**

Modify `src/TrailServiceProvider.php` boot method:

```php
if ($this->app->bound('router')) {
    $this->app['router']->aliasMiddleware('trail', \Trail\Http\Middleware\RecordTrail::class);
}
```

- [ ] **Step 6: Run HTTP tracing test**

Run: `composer test -- --filter HttpTracingTest`

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add src tests
git commit -m "feat: capture HTTP request trails"
```

---

### Task 7: Identity Resolution

**Files:**
- Create: `src/Identity/ResolvedIdentity.php`
- Create: `src/Identity/IdentityResolver.php`
- Create: `src/Identity/Resolvers/AuthGuardResolver.php`
- Create: `src/Identity/Resolvers/RequestPayloadResolver.php`
- Modify: `src/Http/Middleware/RecordTrail.php`
- Modify: `src/Storage/DatabaseTrailStorage.php`
- Create: `tests/Unit/IdentityResolverTest.php`

- [ ] **Step 1: Write identity resolver tests**

Create `tests/Unit/IdentityResolverTest.php`:

```php
<?php

namespace Trail\Tests\Unit;

use Illuminate\Http\Request;
use Trail\Identity\IdentityResolver;
use Trail\Identity\Resolvers\RequestPayloadResolver;
use Trail\Tests\TestCase;

class IdentityResolverTest extends TestCase
{
    public function test_it_resolves_payload_email_as_medium_confidence_identity(): void
    {
        $resolver = new IdentityResolver([
            new RequestPayloadResolver(),
        ]);

        $identity = $resolver->resolve(Request::create('/login', 'POST', [
            'email' => 'user@example.test',
        ]));

        $this->assertSame('payload', $identity->ownerType);
        $this->assertSame(hash('sha256', 'user@example.test'), $identity->ownerId);
        $this->assertSame('email:user@example.test', $identity->ownerLabel);
        $this->assertSame('payload_email', $identity->source);
        $this->assertSame('medium', $identity->confidence);
    }
}
```

- [ ] **Step 2: Run test to verify failure**

Run: `composer test -- --filter IdentityResolverTest`

Expected: FAIL because identity classes do not exist.

- [ ] **Step 3: Create identity value object**

Create `src/Identity/ResolvedIdentity.php`:

```php
<?php

namespace Trail\Identity;

class ResolvedIdentity
{
    public function __construct(
        public ?string $ownerType,
        public ?string $ownerId,
        public ?string $ownerLabel,
        public ?string $source,
        public ?string $confidence,
    ) {
    }

    public static function none(): self
    {
        return new self(null, null, null, null, null);
    }
}
```

- [ ] **Step 4: Create resolver chain**

Create `src/Identity/IdentityResolver.php`:

```php
<?php

namespace Trail\Identity;

use Illuminate\Http\Request;

class IdentityResolver
{
    public function __construct(private array $resolvers)
    {
    }

    public function resolve(Request $request): ResolvedIdentity
    {
        foreach ($this->resolvers as $resolver) {
            $identity = $resolver->resolve($request);

            if ($identity->ownerId !== null) {
                return $identity;
            }
        }

        return ResolvedIdentity::none();
    }
}
```

- [ ] **Step 5: Create request payload resolver**

Create `src/Identity/Resolvers/RequestPayloadResolver.php`:

```php
<?php

namespace Trail\Identity\Resolvers;

use Illuminate\Http\Request;
use Trail\Identity\ResolvedIdentity;

class RequestPayloadResolver
{
    public function resolve(Request $request): ResolvedIdentity
    {
        foreach (['email', 'phone', 'username', 'reference', 'account_number'] as $field) {
            $value = $request->input($field);

            if ($value) {
                return new ResolvedIdentity(
                    'payload',
                    hash('sha256', (string) $value),
                    "{$field}:{$value}",
                    "payload_{$field}",
                    'medium',
                );
            }
        }

        return ResolvedIdentity::none();
    }
}
```

- [ ] **Step 6: Create auth guard resolver**

Create `src/Identity/Resolvers/AuthGuardResolver.php`:

```php
<?php

namespace Trail\Identity\Resolvers;

use Illuminate\Http\Request;
use Trail\Identity\ResolvedIdentity;

class AuthGuardResolver
{
    public function resolve(Request $request): ResolvedIdentity
    {
        $user = $request->user();

        if (! $user) {
            return ResolvedIdentity::none();
        }

        return new ResolvedIdentity(
            $user::class,
            (string) $user->getAuthIdentifier(),
            method_exists($user, 'getEmailForPasswordReset') ? $user->getEmailForPasswordReset() : (string) $user->getAuthIdentifier(),
            'auth_user',
            'high',
        );
    }
}
```

- [ ] **Step 7: Bind identity resolver**

Modify `src/TrailServiceProvider.php` register method:

```php
$this->app->singleton(\Trail\Identity\IdentityResolver::class, function () {
    return new \Trail\Identity\IdentityResolver([
        new \Trail\Identity\Resolvers\AuthGuardResolver(),
        new \Trail\Identity\Resolvers\RequestPayloadResolver(),
    ]);
});
```

- [ ] **Step 8: Add identity to middleware trace attributes**

Modify `src/Http/Middleware/RecordTrail.php` constructor:

```php
public function __construct(
    private TrailManager $trail,
    private TrailStorageDriver $storage,
    private \Trail\Identity\IdentityResolver $identityResolver,
) {
}
```

Before `$this->trail->start([...])`, add:

```php
$identity = $this->identityResolver->resolve($request);
```

Add these attributes inside `start([...])`:

```php
'owner_type' => $identity->ownerType,
'owner_id' => $identity->ownerId,
'owner_label' => $identity->ownerLabel,
'identity_source' => $identity->source,
'identity_confidence' => $identity->confidence,
```

- [ ] **Step 9: Persist identity fields**

Modify `src/Storage/DatabaseTrailStorage.php` create array:

```php
'owner_type' => $trace->attributes['owner_type'] ?? null,
'owner_id' => $trace->attributes['owner_id'] ?? null,
'owner_label' => $trace->attributes['owner_label'] ?? null,
'identity_source' => $trace->attributes['identity_source'] ?? null,
'identity_confidence' => $trace->attributes['identity_confidence'] ?? null,
```

- [ ] **Step 10: Run identity tests**

Run: `composer test -- --filter "IdentityResolverTest|HttpTracingTest"`

Expected: PASS.

- [ ] **Step 11: Commit**

```bash
git add src tests
git commit -m "feat: resolve trail ownership"
```

---

### Task 8: Trail Users And Access

**Files:**
- Create: `src/Models/TrailUser.php`
- Create: `src/Models/TrailSignedLink.php`
- Create: `src/Http/Middleware/AuthorizeTrailDashboard.php`
- Create: `src/Commands/CreateTrailUserCommand.php`
- Create: `database/migrations/0001_01_01_000003_create_trail_users_table.php.stub`
- Create: `database/migrations/0001_01_01_000004_create_trail_signed_links_table.php.stub`
- Modify: `src/TrailServiceProvider.php`
- Create: `tests/Feature/TrailUserCommandTest.php`
- Create: `tests/Feature/DashboardAccessTest.php`

- [ ] **Step 1: Write Trail user command test**

Create `tests/Feature/TrailUserCommandTest.php`:

```php
<?php

namespace Trail\Tests\Feature;

use Illuminate\Support\Facades\Hash;
use Trail\Models\TrailUser;
use Trail\Tests\TestCase;

class TrailUserCommandTest extends TestCase
{
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    public function test_it_creates_a_trail_admin_user(): void
    {
        $this->artisan('trail:user', [
            'email' => 'admin@example.test',
            '--name' => 'Admin',
            '--role' => 'admin',
            '--password' => 'password',
        ])->assertExitCode(0);

        $user = TrailUser::query()->first();

        $this->assertSame('admin@example.test', $user->email);
        $this->assertSame('admin', $user->role);
        $this->assertTrue(Hash::check('password', $user->password));
    }
}
```

- [ ] **Step 2: Create user migrations and model**

Create `database/migrations/0001_01_01_000003_create_trail_users_table.php.stub`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trail_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->index();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trail_users');
    }
};
```

Create `src/Models/TrailUser.php`:

```php
<?php

namespace Trail\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class TrailUser extends Authenticatable
{
    protected $guarded = [];

    protected $hidden = ['password', 'remember_token'];

    public function canViewTechnicalContext(): bool
    {
        return in_array($this->role, ['developer', 'admin'], true);
    }

    public function canManageTrailUsers(): bool
    {
        return $this->role === 'admin';
    }
}
```

- [ ] **Step 3: Create user command**

Create `src/Commands/CreateTrailUserCommand.php`:

```php
<?php

namespace Trail\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Trail\Models\TrailUser;

class CreateTrailUserCommand extends Command
{
    protected $signature = 'trail:user {email} {--name=} {--role=admin} {--password=}';

    protected $description = 'Create a Trail dashboard user.';

    public function handle(): int
    {
        $email = $this->argument('email');
        $password = $this->option('password') ?: $this->secret('Password');

        TrailUser::query()->create([
            'name' => $this->option('name') ?: $email,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => $this->option('role'),
        ]);

        $this->info("Trail user created: {$email}");

        return self::SUCCESS;
    }
}
```

- [ ] **Step 4: Register command**

Modify `src/TrailServiceProvider.php` boot method:

```php
if ($this->app->runningInConsole()) {
    $this->commands([
        \Trail\Commands\CreateTrailUserCommand::class,
    ]);
}
```

- [ ] **Step 5: Run Trail user command test**

Run: `composer test -- --filter TrailUserCommandTest`

Expected: PASS.

- [ ] **Step 6: Create access middleware**

Create `src/Http/Middleware/AuthorizeTrailDashboard.php`:

```php
<?php

namespace Trail\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeTrailDashboard
{
    public function handle(Request $request, Closure $next)
    {
        $this->ensureIpIsAllowed($request);

        if (config('trail.access.mode') === 'gate') {
            abort_unless(Gate::allows(config('trail.access.gate')), Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }

    private function ensureIpIsAllowed(Request $request): void
    {
        $allowlist = config('trail.access.ip_allowlist', []);

        if ($allowlist === []) {
            return;
        }

        abort_unless(in_array($request->ip(), $allowlist, true), Response::HTTP_FORBIDDEN);
    }
}
```

- [ ] **Step 7: Commit**

```bash
git add database src tests
git commit -m "feat: add Trail dashboard access users"
```

---

### Task 9: Dashboard Routes And Inertia Shell

**Files:**
- Create: `routes/trail.php`
- Create: `src/Http/Controllers/TraceIndexController.php`
- Create: `src/Http/Controllers/TraceShowController.php`
- Create: `src/Http/Controllers/UserJourneyController.php`
- Create: `resources/views/app.blade.php`
- Modify: `src/TrailServiceProvider.php`
- Create: `tests/Feature/DashboardRoutesTest.php`

- [ ] **Step 1: Write dashboard route test**

Create `tests/Feature/DashboardRoutesTest.php`:

```php
<?php

namespace Trail\Tests\Feature;

use Trail\Tests\TestCase;

class DashboardRoutesTest extends TestCase
{
    public function test_trace_index_route_is_registered(): void
    {
        $this->get('/trail/traces')->assertOk();
    }
}
```

- [ ] **Step 2: Create dashboard routes**

Create `routes/trail.php`:

```php
<?php

use Illuminate\Support\Facades\Route;
use Trail\Http\Controllers\TraceIndexController;
use Trail\Http\Controllers\TraceShowController;
use Trail\Http\Controllers\UserJourneyController;
use Trail\Http\Middleware\AuthorizeTrailDashboard;

Route::prefix(config('trail.path', 'trail'))
    ->middleware(array_merge(config('trail.middleware', ['web']), [AuthorizeTrailDashboard::class]))
    ->group(function () {
        Route::get('/traces', TraceIndexController::class)->name('trail.traces.index');
        Route::get('/traces/{trace}', TraceShowController::class)->name('trail.traces.show');
        Route::get('/journeys/users/{ownerId}', UserJourneyController::class)->name('trail.journeys.users.show');
    });
```

- [ ] **Step 3: Create controllers**

Create `src/Http/Controllers/TraceIndexController.php`:

```php
<?php

namespace Trail\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Trail\Models\TrailTrace;

class TraceIndexController
{
    public function __invoke(Request $request)
    {
        return Inertia::render('Traces/Index', [
            'traces' => TrailTrace::query()->latest()->limit(50)->get(),
        ]);
    }
}
```

Create `src/Http/Controllers/TraceShowController.php`:

```php
<?php

namespace Trail\Http\Controllers;

use Inertia\Inertia;
use Trail\Models\TrailTrace;

class TraceShowController
{
    public function __invoke(TrailTrace $trace)
    {
        return Inertia::render('Traces/Show', [
            'trace' => $trace->load('steps'),
        ]);
    }
}
```

Create `src/Http/Controllers/UserJourneyController.php`:

```php
<?php

namespace Trail\Http\Controllers;

use Inertia\Inertia;
use Trail\Models\TrailTrace;

class UserJourneyController
{
    public function __invoke(string $ownerId)
    {
        return Inertia::render('Journeys/User', [
            'ownerId' => $ownerId,
            'traces' => TrailTrace::query()
                ->where('owner_id', $ownerId)
                ->orderBy('started_at')
                ->get(),
        ]);
    }
}
```

- [ ] **Step 4: Create Inertia root view**

Create `resources/views/app.blade.php`:

```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Trail</title>
    @viteReactRefresh
    @vite('resources/js/app.tsx')
</head>
<body>
    @inertia
</body>
</html>
```

- [ ] **Step 5: Load routes and views**

Modify `src/TrailServiceProvider.php` boot method:

```php
$this->loadRoutesFrom(__DIR__ . '/../routes/trail.php');
$this->loadViewsFrom(__DIR__ . '/../resources/views', 'trail');
```

- [ ] **Step 6: Run route test**

Run: `composer test -- --filter DashboardRoutesTest`

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add routes resources src tests
git commit -m "feat: add Trail dashboard routes"
```

---

### Task 10: React Dashboard Pages

**Files:**
- Create: `package.json`
- Create: `vite.config.ts`
- Create: `resources/js/app.tsx`
- Create: `resources/js/Pages/Traces/Index.tsx`
- Create: `resources/js/Pages/Traces/Show.tsx`
- Create: `resources/js/Pages/Journeys/User.tsx`
- Create: `resources/js/Components/TraceTimeline.tsx`
- Create: `resources/js/Components/JsonPreview.tsx`

- [ ] **Step 1: Add frontend dependencies**

Create `package.json`:

```json
{
  "private": true,
  "type": "module",
  "scripts": {
    "build": "vite build",
    "dev": "vite"
  },
  "dependencies": {
    "@inertiajs/react": "^1.0.0",
    "@vitejs/plugin-react": "^4.0.0",
    "vite": "^5.0.0",
    "react": "^18.2.0",
    "react-dom": "^18.2.0"
  },
  "devDependencies": {
    "typescript": "^5.0.0"
  }
}
```

- [ ] **Step 2: Add Vite config**

Create `vite.config.ts`:

```ts
import react from '@vitejs/plugin-react';
import { defineConfig } from 'vite';

export default defineConfig({
  plugins: [react()],
  build: {
    manifest: true,
    outDir: 'dist',
    rollupOptions: {
      input: 'resources/js/app.tsx',
    },
  },
});
```

- [ ] **Step 3: Create Inertia app entry**

Create `resources/js/app.tsx`:

```tsx
import React from 'react';
import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';

createInertiaApp({
  resolve: async (name) => {
    const pages = import.meta.glob('./Pages/**/*.tsx');
    const page = pages[`./Pages/${name}.tsx`];

    if (!page) {
      throw new Error(`Unknown Trail page: ${name}`);
    }

    return page();
  },
  setup({ el, App, props }) {
    createRoot(el).render(<App {...props} />);
  },
});
```

- [ ] **Step 4: Create JSON preview component**

Create `resources/js/Components/JsonPreview.tsx`:

```tsx
import React from 'react';

type Props = {
  value: unknown;
};

export function JsonPreview({ value }: Props) {
  return (
    <pre style={{ whiteSpace: 'pre-wrap', fontSize: 12 }}>
      {JSON.stringify(value, null, 2)}
    </pre>
  );
}
```

- [ ] **Step 5: Create timeline component**

Create `resources/js/Components/TraceTimeline.tsx`:

```tsx
import React, { useState } from 'react';
import { JsonPreview } from './JsonPreview';

type Step = {
  id: number;
  message: string;
  context: unknown;
  recorded_at: string;
};

type Props = {
  steps: Step[];
  canViewTechnicalContext: boolean;
};

export function TraceTimeline({ steps, canViewTechnicalContext }: Props) {
  const [openStepId, setOpenStepId] = useState<number | null>(null);

  return (
    <div>
      {steps.map((step) => (
        <div key={step.id} style={{ borderBottom: '1px solid #e5e7eb', padding: 12 }}>
          <button type="button" onClick={() => setOpenStepId(openStepId === step.id ? null : step.id)}>
            {step.message}
          </button>
          {openStepId === step.id && canViewTechnicalContext && <JsonPreview value={step.context} />}
        </div>
      ))}
    </div>
  );
}
```

- [ ] **Step 6: Create trace index page**

Create `resources/js/Pages/Traces/Index.tsx`:

```tsx
import React from 'react';
import { Link } from '@inertiajs/react';

type Trace = {
  id: number;
  trace_id: string;
  method: string;
  path: string;
  status_code: number;
  owner_label: string | null;
  started_at: string | null;
};

export default function TraceIndex({ traces }: { traces: Trace[] }) {
  return (
    <main style={{ padding: 24 }}>
      <h1>Traces</h1>
      <table>
        <tbody>
          {traces.map((trace) => (
            <tr key={trace.id}>
              <td>{trace.status_code}</td>
              <td>{trace.method}</td>
              <td>{trace.path}</td>
              <td>{trace.owner_label}</td>
              <td><Link href={`/trail/traces/${trace.id}`}>Open</Link></td>
            </tr>
          ))}
        </tbody>
      </table>
    </main>
  );
}
```

- [ ] **Step 7: Create trace show page**

Create `resources/js/Pages/Traces/Show.tsx`:

```tsx
import React from 'react';
import { TraceTimeline } from '../../Components/TraceTimeline';
import { JsonPreview } from '../../Components/JsonPreview';

export default function TraceShow({ trace, canViewTechnicalContext = true }: any) {
  return (
    <main style={{ padding: 24 }}>
      <h1>{trace.method} {trace.path}</h1>
      <p>Status: {trace.status_code}</p>
      <p>Owner: {trace.owner_label || 'Unknown'}</p>
      {canViewTechnicalContext && <JsonPreview value={trace.request} />}
      <TraceTimeline steps={trace.steps || []} canViewTechnicalContext={canViewTechnicalContext} />
    </main>
  );
}
```

- [ ] **Step 8: Create user journey page**

Create `resources/js/Pages/Journeys/User.tsx`:

```tsx
import React from 'react';
import { Link } from '@inertiajs/react';

export default function UserJourney({ ownerId, traces }: any) {
  return (
    <main style={{ padding: 24 }}>
      <h1>User Journey</h1>
      <p>{ownerId}</p>
      {traces.map((trace: any) => (
        <div key={trace.id} style={{ borderBottom: '1px solid #e5e7eb', padding: 12 }}>
          <strong>{trace.status_code}</strong> {trace.method} {trace.path}
          <Link href={`/trail/traces/${trace.id}`} style={{ marginLeft: 12 }}>Open</Link>
        </div>
      ))}
    </main>
  );
}
```

- [ ] **Step 9: Build assets**

Run: `npm install`

Expected: dependencies install successfully.

Run: `npm run build`

Expected: Vite writes `dist/manifest.json` and compiled assets.

- [ ] **Step 10: Commit**

```bash
git add package.json vite.config.ts resources dist
git commit -m "feat: add Inertia React dashboard"
```

---

### Task 11: Non-Blocking Writes

**Files:**
- Create: `src/Jobs/FlushTrailTrace.php`
- Create: `src/Storage/NullTrailStorage.php`
- Modify: `src/Http/Middleware/RecordTrail.php`
- Modify: `src/TrailServiceProvider.php`
- Create: `tests/Feature/StorageFailureTest.php`

- [ ] **Step 1: Write storage failure test**

Create `tests/Feature/StorageFailureTest.php`:

```php
<?php

namespace Trail\Tests\Feature;

use Exception;
use Illuminate\Support\Facades\Route;
use Trail\Http\Middleware\RecordTrail;
use Trail\Storage\TrailStorageDriver;
use Trail\Tests\TestCase;
use Trail\TraceContext;

class StorageFailureTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        Route::middleware(RecordTrail::class)->get('/ok', fn () => 'ok');
    }

    public function test_storage_failure_does_not_break_business_response(): void
    {
        $this->app->bind(TrailStorageDriver::class, fn () => new class implements TrailStorageDriver {
            public function store(TraceContext $trace): void
            {
                throw new Exception('storage down');
            }
        });

        $this->get('/ok')->assertOk()->assertSee('ok');
    }
}
```

- [ ] **Step 2: Run test to verify failure**

Run: `composer test -- --filter StorageFailureTest`

Expected: FAIL because storage exception bubbles.

- [ ] **Step 3: Catch storage failures in middleware**

Modify both storage calls in `src/Http/Middleware/RecordTrail.php`:

```php
if ($trace) {
    try {
        $this->storage->store($trace);
    } catch (\Throwable $storageException) {
        report($storageException);
    }
}
```

- [ ] **Step 4: Create queued flush job**

Create `src/Jobs/FlushTrailTrace.php`:

```php
<?php

namespace Trail\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Trail\Storage\TrailStorageDriver;
use Trail\TraceContext;

class FlushTrailTrace implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private TraceContext $trace)
    {
    }

    public function handle(TrailStorageDriver $storage): void
    {
        $storage->store($this->trace);
    }
}
```

- [ ] **Step 5: Run storage failure test**

Run: `composer test -- --filter StorageFailureTest`

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add src tests
git commit -m "feat: isolate trail storage failures"
```

---

### Task 12: Retention Pruning

**Files:**
- Create: `src/Commands/PruneTrailCommand.php`
- Modify: `src/TrailServiceProvider.php`
- Create: `tests/Feature/PruneTrailCommandTest.php`

- [ ] **Step 1: Write prune command test**

Create `tests/Feature/PruneTrailCommandTest.php`:

```php
<?php

namespace Trail\Tests\Feature;

use Illuminate\Support\Carbon;
use Trail\Models\TrailTrace;
use Trail\Tests\TestCase;

class PruneTrailCommandTest extends TestCase
{
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    public function test_it_prunes_traces_older_than_retention_days(): void
    {
        TrailTrace::query()->create([
            'trace_id' => 'old',
            'created_at' => Carbon::now()->subDays(91),
            'updated_at' => Carbon::now()->subDays(91),
        ]);

        TrailTrace::query()->create([
            'trace_id' => 'new',
            'created_at' => Carbon::now()->subDays(10),
            'updated_at' => Carbon::now()->subDays(10),
        ]);

        $this->artisan('trail:prune')->assertExitCode(0);

        $this->assertFalse(TrailTrace::query()->where('trace_id', 'old')->exists());
        $this->assertTrue(TrailTrace::query()->where('trace_id', 'new')->exists());
    }
}
```

- [ ] **Step 2: Create prune command**

Create `src/Commands/PruneTrailCommand.php`:

```php
<?php

namespace Trail\Commands;

use Illuminate\Console\Command;
use Trail\Models\TrailTrace;

class PruneTrailCommand extends Command
{
    protected $signature = 'trail:prune';

    protected $description = 'Delete Trail traces older than configured retention.';

    public function handle(): int
    {
        $days = (int) config('trail.retention.days', 90);
        $cutoff = now()->subDays($days);

        $deleted = TrailTrace::query()
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info("Pruned {$deleted} Trail traces.");

        return self::SUCCESS;
    }
}
```

- [ ] **Step 3: Register prune command**

Modify `src/TrailServiceProvider.php` command registration:

```php
$this->commands([
    \Trail\Commands\CreateTrailUserCommand::class,
    \Trail\Commands\PruneTrailCommand::class,
]);
```

- [ ] **Step 4: Run prune test**

Run: `composer test -- --filter PruneTrailCommandTest`

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src tests
git commit -m "feat: add trail retention pruning"
```

---

### Task 13: Signed URL Access

**Files:**
- Create: `src/Commands/CreateSignedTrailLinkCommand.php`
- Modify: `src/Models/TrailSignedLink.php`
- Modify: `src/Http/Middleware/AuthorizeTrailDashboard.php`
- Modify: `src/TrailServiceProvider.php`
- Create: `tests/Feature/SignedTrailLinkTest.php`

- [ ] **Step 1: Write signed link test**

Create `tests/Feature/SignedTrailLinkTest.php`:

```php
<?php

namespace Trail\Tests\Feature;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Trail\Models\TrailSignedLink;
use Trail\Tests\TestCase;

class SignedTrailLinkTest extends TestCase
{
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    public function test_valid_signed_token_allows_access(): void
    {
        $token = (string) Str::uuid();

        TrailSignedLink::query()->create([
            'token_hash' => hash('sha256', $token),
            'scope' => 'dashboard',
            'expires_at' => Carbon::now()->addHour(),
        ]);

        config(['trail.access.mode' => 'signed_url']);

        $this->get('/trail/traces?trail_token=' . $token)->assertOk();
    }
}
```

- [ ] **Step 2: Create signed links migration and model**

Create `database/migrations/0001_01_01_000004_create_trail_signed_links_table.php.stub`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trail_signed_links', function (Blueprint $table) {
            $table->id();
            $table->string('token_hash')->unique();
            $table->string('scope')->default('dashboard');
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trail_signed_links');
    }
};
```

Create `src/Models/TrailSignedLink.php`:

```php
<?php

namespace Trail\Models;

use Illuminate\Database\Eloquent\Model;

class TrailSignedLink extends Model
{
    protected $guarded = [];

    protected $casts = [
        'expires_at' => 'datetime',
    ];
}
```

- [ ] **Step 3: Update access middleware for signed URL mode**

Modify `src/Http/Middleware/AuthorizeTrailDashboard.php` after gate handling:

```php
if (config('trail.access.mode') === 'signed_url') {
    $token = $request->query('trail_token');

    abort_unless($token, Response::HTTP_FORBIDDEN);

    $exists = \Trail\Models\TrailSignedLink::query()
        ->where('token_hash', hash('sha256', (string) $token))
        ->where('expires_at', '>', now())
        ->exists();

    abort_unless($exists, Response::HTTP_FORBIDDEN);
}
```

- [ ] **Step 4: Create signed URL command**

Create `src/Commands/CreateSignedTrailLinkCommand.php`:

```php
<?php

namespace Trail\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Trail\Models\TrailSignedLink;

class CreateSignedTrailLinkCommand extends Command
{
    protected $signature = 'trail:signed-link {--scope=dashboard} {--minutes=}';

    protected $description = 'Create a temporary signed Trail dashboard link.';

    public function handle(): int
    {
        $token = (string) Str::uuid();
        $minutes = (int) ($this->option('minutes') ?: config('trail.access.signed_url_ttl_minutes', 60));

        TrailSignedLink::query()->create([
            'token_hash' => hash('sha256', $token),
            'scope' => $this->option('scope'),
            'expires_at' => now()->addMinutes($minutes),
        ]);

        $this->line(url(config('trail.path', 'trail') . '/traces?trail_token=' . $token));

        return self::SUCCESS;
    }
}
```

- [ ] **Step 5: Register signed link command**

Modify command registration:

```php
$this->commands([
    \Trail\Commands\CreateTrailUserCommand::class,
    \Trail\Commands\CreateSignedTrailLinkCommand::class,
    \Trail\Commands\PruneTrailCommand::class,
]);
```

- [ ] **Step 6: Run signed link test**

Run: `composer test -- --filter SignedTrailLinkTest`

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add database src tests
git commit -m "feat: add signed Trail dashboard links"
```

---

### Task 14: Documentation And Integration Check

**Files:**
- Create: `README.md`
- Create: `docs/configuration.md`
- Create: `docs/dashboard.md`
- Create: `docs/storage.md`
- Modify: `composer.json`

- [ ] **Step 1: Create README**

Create `README.md`:

```markdown
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
Route::middleware(['trail'])->group(function () {
    Route::post('/transfer', TransferController::class);
});
```

## Add Developer Steps

```php
step('charging wallet', $wallet, $amount, $response);
```

Trail automatically attaches the step to the active request trace, normalizes context, sanitizes sensitive data, and stores it through the configured driver.
```

- [ ] **Step 2: Create configuration docs**

Create `docs/configuration.md`:

```markdown
# Configuration

Trail publishes `config/trail.php`.

Important settings:

- `enabled`: turns Trail capture on or off.
- `path`: dashboard path, default `trail`.
- `access.mode`: `trail_users`, `gate`, or `signed_url`.
- `access.ip_allowlist`: optional perimeter allowlist.
- `storage.driver`: default `database`.
- `storage.write_mode`: `sync`, `after_response`, or `queue`.
- `retention.days`: default `90`.
- `sanitization.sensitive_keys`: keys masked before storage.
```

- [ ] **Step 3: Create dashboard docs**

Create `docs/dashboard.md`:

```markdown
# Dashboard

Trail uses an Inertia + React dashboard.

Roles:

- `support`: journey status, trace status, and step messages.
- `developer`: sanitized technical context and stack traces.
- `admin`: developer visibility plus Trail user management.

Basic auth is not supported. Use Trail users, host gate mode, or signed URLs.
```

- [ ] **Step 4: Create storage docs**

Create `docs/storage.md`:

```markdown
# Storage

Trail stores traces through `Trail\Storage\TrailStorageDriver`.

The default implementation is `Trail\Storage\DatabaseTrailStorage`.

Storage implementations must not throw exceptions into business flow. Middleware catches and reports storage failures so a failed trail write does not fail the application request.
```

- [ ] **Step 5: Run full backend test suite**

Run: `composer test`

Expected: PASS.

- [ ] **Step 6: Build dashboard assets**

Run: `npm run build`

Expected: PASS and compiled dashboard assets in `dist`.

- [ ] **Step 7: Commit**

```bash
git add README.md docs composer.json package.json vite.config.ts resources src tests database dist
git commit -m "docs: document Trail package usage"
```

---

## Self-Review

Spec coverage:

- `step(...)` developer API is implemented in Tasks 1, 3, and 4.
- Automatic HTTP tracing is implemented in Task 6.
- Identity resolution is implemented in Task 7.
- User journey and trace dashboard routes are implemented in Task 9.
- Inertia + React dashboard is implemented in Task 10.
- Trail users, signed URLs, gate mode, and IP allowlist are implemented in Tasks 8 and 13.
- Database storage, driver contract, and storage failure isolation are implemented in Tasks 5 and 11.
- Retention pruning is implemented in Task 12.
- Sanitization is implemented in Task 4.
- Documentation is implemented in Task 14.

Known follow-up after v1:

- Add explicit external storage drivers after the database driver contract is proven.
- Add richer model-specific identity resolvers for wallet/account-number-heavy host apps.
- Add support for queued job trace propagation.
- Add polished dashboard styling after core flows work.
