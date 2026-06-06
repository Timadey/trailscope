# Configuration

TrailScope publishes `config/trail.php`.

Important settings:

- `enabled`: turns TrailScope capture on or off.
- `path`: dashboard path, default `trail`.
- `access.mode`: `trail_users`, `gate`, or `signed_url`.
- `access.ip_allowlist`: optional perimeter allowlist.
- `storage.driver`: `database` or `redis`.
- `storage.write_mode`: `sync`, `after_response`, or `queue`.
- `capture.except_paths`: request path patterns that should not be traced.
- `capture.except_route_names`: route-name patterns that should not be traced.
- `steps.infer_variable_names`: best-effort variable-name inference for simple positional `step()` context.
- `retention.days`: default `90`.
- `sanitization.sensitive_keys`: keys masked before storage.
- `identity.payload_keys`: request payload keys used to resolve journey ownership.
