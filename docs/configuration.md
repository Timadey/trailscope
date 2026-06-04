# Configuration

Trail publishes `config/trail.php`.

Important settings:

- `enabled`: turns Trail capture on or off.
- `path`: dashboard path, default `trail`.
- `access.mode`: `trail_users`, `gate`, or `signed_url`.
- `access.ip_allowlist`: optional perimeter allowlist.
- `storage.driver`: `database` or `redis`.
- `storage.write_mode`: `sync`, `after_response`, or `queue`.
- `retention.days`: default `90`.
- `sanitization.sensitive_keys`: keys masked before storage.
