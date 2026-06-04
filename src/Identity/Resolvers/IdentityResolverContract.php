<?php

namespace Trail\Identity\Resolvers;

use Illuminate\Http\Request;
use Trail\Identity\ResolvedIdentity;
use Trail\TraceContext;

interface IdentityResolverContract
{
    public function resolve(?Request $request = null, ?TraceContext $trace = null, array $context = []): ?ResolvedIdentity;
}
