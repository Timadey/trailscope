# Storage

TrailScope stores traces through `Trail\Storage\TrailStorageDriver`.

The default implementation is `Trail\Storage\DatabaseTrailStorage`.

Redis is implemented by `Trail\Storage\RedisTrailStorage`. It stores each trace payload under a TTL key and maintains sorted-set indexes for recent traces and owner journeys.

Storage implementations must not throw exceptions into business flow. Middleware catches and reports storage failures so a failed TrailScope write does not fail the application request.
