# Trail Package Design

## Purpose

Trail is a standalone Laravel package for request and user journey observability. It should feel similar in spirit to Laravel Telescope or Horizon, but focused on answering two questions quickly:

- What happened inside this request or execution?
- What happened across this user's journey?

The package must improve developer experience by keeping the developer-facing API very small. Developers should not need to manually create traces, attach users, manage trace IDs, or understand storage internals.

## Developer Experience

The primary developer API is a single helper:

```php
step('charging wallet', $wallet, $amount, $response);
```

The helper signature should accept a message and any number of context values:

```php
step(string $message, mixed ...$context): void
```

Developers can pass models, requests, responses, exceptions, arrays, scalars, or plain objects. The package normalizes and sanitizes those values into structured context automatically.

Arrays with explicit keys remain supported, but they are not required:

```php
step('provider responded', ['reference' => $reference, 'status' => $status]);
```

The desired mental model is:

```php
step('what is happening', ...thingsThatMatter);
```

## Automatic HTTP Tracing

Trail should automatically trace HTTP requests through middleware. A trace is created at request start and finalized after the response or exception.

At request start, Trail records:

- trace ID
- HTTP method and path
- route name
- controller and action when available
- sanitized request input
- request headers only if enabled
- IP and user agent only if enabled
- start timestamp

At request end, Trail records:

- response status code
- duration
- sanitized response summary
- exception class and message when failed
- stack trace only for roles allowed to view technical context
- a final leaving event

This automatic capture means a useful trail exists even if no developer calls `step()`.

## Service Flow Tracing

Fully automatic service-method interception is not required for v1. In Laravel applications, many services may be created directly with `new`, so container-only interception would be incomplete.

For v1, services are traced through `step(...)` calls attached to the active request trace. Future versions may add optional service wrappers, attributes, or container decorators, but those should not complicate the initial developer API.

## Context Normalization

Trail should include a `ContextNormalizer` pipeline that converts context arguments into safe structured data.

Expected handling:

- Eloquent models: class, primary key, configured safe attributes, and known identity fields.
- Requests: method, path, route, and sanitized input.
- Responses: status code and sanitized body preview.
- Exceptions: class, message, file, line, and stack trace when permitted.
- Arrays: recursively sanitized.
- Scalars: stored under generated keys such as `value_1`, unless paired with explicit keys.
- Objects: class name and safe public/exportable fields when possible.

Context normalization also feeds identity resolution. If a step receives a `User`, `Wallet`, transaction, account number, phone, email, or reference, Trail can use that context to improve ownership attribution.

## Identity Resolution

Trail identifies who a trace belongs to through a resolver chain. The resolver should be a separate component so host applications can extend or replace it.

Default identity sources:

- authenticated users from configured guards
- route models such as `User` and `Wallet`
- route parameters such as account numbers or references
- sanitized or hashed payload identifiers such as phone, email, username, BVN, or NIN
- context passed to `step(...)`

Trail stores:

- resolved owner type
- resolved owner ID where available
- display-safe owner label where available
- identity source, such as `auth_user`, `route_wallet`, `payload_phone`, or `step_context_wallet`
- confidence, such as `high`, `medium`, or `low`

Sensitive identifiers must be masked or hashed before storage.

## Journeys

A trace is one request or execution.

A user journey is the chronological set of traces linked to the same resolved owner. In v1, this is enough to support support/admin investigation without requiring developers to manually tag business journeys.

Future versions may add business journey grouping for flows such as registration, transfer, card creation, account upgrade, provider callbacks, and queued jobs. These groupings can be inferred from references, route names, controller actions, and transaction identifiers.

## Dashboard

The dashboard should be built with Inertia and React. Blade-only is not preferred because trace exploration is interaction-heavy.

The dashboard should include:

- trace search
- user journey search
- filters by date range, route, status, owner, reference, wallet/account number, and trace ID
- trace timeline view
- user journey timeline view
- expandable steps
- expandable sanitized context
- failure highlighting
- role-aware visibility

The package should own the dashboard UI. The host app should only configure path, access, storage, retention, and sanitization rules.

## Access Management

Trail should not assume the host application has admin, support, or developer users. The default access mode is package-managed users.

Access modes:

- `trail_users`: package-owned dashboard users.
- `gate`: host app controls access through a configured Laravel Gate.
- `signed_url`: temporary signed access links generated by Artisan.

Additional perimeter security:

- `ip_allowlist`: optional list of IPs or subnets allowed to access the dashboard, applied on top of the selected access mode.

Basic auth is intentionally excluded.

Trail user roles:

- `support`: can search and view customer journeys, statuses, and step messages.
- `developer`: can view sanitized technical context, errors, payload summaries, and stack traces.
- `admin`: has developer visibility plus Trail user and access management.

Support users must not see raw params, raw responses, headers, provider payloads, SQL, stack traces, or technical context.

## Storage

Trail uses driver-based storage from day one.

The default driver is the host application database because it is easiest to install and operate. External storage must be supported through a storage contract so large deployments can avoid growing the business database.

Storage requirements:

- host database driver by default
- external driver contract
- non-blocking write modes
- storage failures must never break the business request
- maximum context size
- maximum steps per trace
- payload truncation
- optional sampling for successful traces
- always record failed traces unless disabled explicitly

Suggested write modes:

- `sync`
- `after_response`
- `queue`

The recommended default is `after_response` with the database driver.

## Retention

Retention defaults to 90 days and must be configurable.

Database storage should include a prune command:

```bash
php artisan trail:prune
```

External drivers are responsible for their own cleanup strategy, but the package contract should expose pruning where practical.

Future support may include separate retention policies for successful, failed, and high-risk traces.

## Sanitization

Sanitization is always applied before persistence and before optional application-log mirroring.

The package should provide configurable sensitive keys, including defaults for:

- password
- pin
- token
- authorization
- signature
- secret
- bvn
- nin
- otp
- card
- account number where masking is required

Sanitization should support masking, hashing, dropping fields, and truncating large payloads.

## Application Log Mirroring

Trail may optionally mirror step messages and trace lifecycle events to the host application's normal logs.

This must be configurable and sanitized. Mirroring should not be required for dashboard functionality.

## Package Configuration

The package should publish `config/trail.php`.

Core configuration areas:

- enabled state
- dashboard path
- access mode
- Trail user settings
- signed URL settings
- IP allowlist
- storage driver
- write mode
- retention days
- sanitization keys and strategy
- request and response capture limits
- successful trace sampling
- app-log mirroring

## Data Model

The database driver should use a compact schema that supports search and timeline views.

Suggested tables:

- `trail_users`: package dashboard users.
- `trail_traces`: request/execution-level records.
- `trail_steps`: timeline entries and developer breadcrumbs.
- `trail_signed_links`: temporary access links.

Optional future table:

- `trail_journeys`: explicit business journey grouping once that feature is needed.

For v1, user journeys can be derived from owner identity and trace timestamps rather than requiring a dedicated journey table.

## Non-Goals For V1

The first version should not include:

- automatic interception of every service method
- OpenTelemetry compatibility
- SQL query capture by default
- raw payload viewing for support users
- mandatory external storage
- a separate SPA API-only dashboard
- business journey tagging as a required developer API

## Implementation Phases

### Phase 1: Core Package

- Service provider
- configuration publishing
- helper registration
- trace manager
- context normalizer
- identity resolver
- HTTP middleware
- database storage driver
- migrations
- prune command

### Phase 2: Dashboard Access

- Trail user model and authentication
- roles and permissions
- signed URL support
- IP allowlist middleware
- optional host gate mode

### Phase 3: Inertia React Dashboard

- trace list
- trace detail timeline
- user journey timeline
- filters and search
- role-aware context visibility
- failure highlighting

### Phase 4: Storage And Reliability

- external storage contract
- after-response write mode
- queue write mode
- storage failure isolation
- sampling
- size limits and truncation

### Phase 5: Host App Integration

- install package in a Laravel host app
- enable middleware
- configure identity guards
- add `step(...)` calls to critical service flows
- validate support and developer dashboard workflows

## Open Risks

- Inertia and React packaging adds build complexity compared with Blade.
- Identity resolution will improve over time as real host application patterns are discovered.
- External storage support requires a stable contract before implementing specific drivers.
- Non-blocking writes need careful handling so data is not lost silently during high traffic or queue failures.

## Approval Summary

The approved direction is a standalone Laravel package with:

- tiny developer API centered on `step(...)`
- automatic HTTP tracing
- automatic identity resolution
- user journey dashboard
- Inertia and React UI
- package-managed Trail users by default
- signed URL and gate access options
- IP allowlist as extra perimeter control
- no basic auth
- role-based technical visibility
- database storage by default
- external storage support from day one
- non-blocking and failure-safe writes
- configurable 90-day default retention
- always-on sanitization
