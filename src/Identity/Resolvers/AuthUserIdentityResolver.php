<?php

namespace Trail\Identity\Resolvers;

use Illuminate\Http\Request;
use Trail\Identity\ResolvedIdentity;
use Trail\TraceContext;

class AuthUserIdentityResolver implements IdentityResolverContract
{
    public function resolve(?Request $request = null, ?TraceContext $trace = null, array $context = []): ?ResolvedIdentity
    {
        $user = $request?->user();

        if (! $user) {
            return null;
        }

        return new ResolvedIdentity(
            ownerType: $user::class,
            ownerId: (string) $user->getAuthIdentifier(),
            ownerLabel: method_exists($user, 'getEmailForVerification') ? $user->getEmailForVerification() : (string) $user->getAuthIdentifier(),
            source: 'auth_user',
            confidence: 'high',
        );
    }
}
