<?php

namespace Trail\Tests\Unit;

use Illuminate\Http\Request;
use Trail\Identity\IdentityResolver;
use Trail\Identity\Resolvers\RequestPayloadIdentityResolver;
use Trail\Tests\TestCase;

class IdentityResolverTest extends TestCase
{
    public function test_it_resolves_identity_from_payload_reference(): void
    {
        $request = Request::create('/callback', 'POST', ['reference' => 'txn_123']);
        $resolver = new IdentityResolver([
            new RequestPayloadIdentityResolver(),
        ]);

        $identity = $resolver->resolve($request);

        $this->assertSame('reference', $identity?->ownerType);
        $this->assertSame(hash('sha256', 'txn_123'), $identity?->ownerId);
        $this->assertSame('payload_reference', $identity?->source);
    }
}
