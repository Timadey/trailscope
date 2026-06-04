<?php

namespace Trail\Identity\Resolvers;

use Illuminate\Http\Request;
use Trail\Identity\ResolvedIdentity;
use Trail\TraceContext;

class RequestPayloadIdentityResolver implements IdentityResolverContract
{
    public function resolve(?Request $request = null, ?TraceContext $trace = null, array $context = []): ?ResolvedIdentity
    {
        if (! $request) {
            return null;
        }

        foreach (config('trail.identity.payload_keys', []) as $key) {
            $value = $request->input($key);

            if ($value === null || $value === '') {
                continue;
            }

            return new ResolvedIdentity(
                ownerType: $this->ownerTypeFor($key),
                ownerId: hash('sha256', (string) $value),
                ownerLabel: $this->mask((string) $value),
                source: "payload_{$key}",
                confidence: in_array($key, ['user_id', 'wallet_id', 'reference'], true) ? 'medium' : 'low',
            );
        }

        return null;
    }

    private function ownerTypeFor(string $key): string
    {
        return match ($key) {
            'user_id' => 'user',
            'wallet_id' => 'wallet',
            default => $key,
        };
    }

    private function mask(string $value): string
    {
        if (strlen($value) <= 4) {
            return str_repeat('*', strlen($value));
        }

        return substr($value, 0, 2) . str_repeat('*', max(strlen($value) - 4, 0)) . substr($value, -2);
    }
}
