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
