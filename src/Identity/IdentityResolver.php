<?php

namespace Trail\Identity;

use Illuminate\Http\Request;
use Trail\TraceContext;

class IdentityResolver
{
    public function __construct(private iterable $resolvers)
    {
    }

    public function resolve(?Request $request = null, ?TraceContext $trace = null, array $context = []): ?ResolvedIdentity
    {
        foreach ($this->resolvers as $resolver) {
            $identity = $resolver->resolve($request, $trace, $context);

            if ($identity) {
                return $identity;
            }
        }

        return null;
    }
}
