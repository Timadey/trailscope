<?php

namespace Trail\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Trail\Models\TrailSignedLink;

class CreateSignedTrailLinkCommand extends Command
{
    protected $signature = 'trail:signed-link {--scope=dashboard} {--minutes=}';

    protected $description = 'Create a temporary signed Trail dashboard link.';

    public function handle(): int
    {
        $token = (string) Str::uuid();
        $minutes = (int) ($this->option('minutes') ?: config('trail.access.signed_url_ttl_minutes', 60));

        TrailSignedLink::query()->create([
            'token_hash' => hash('sha256', $token),
            'scope' => $this->option('scope'),
            'expires_at' => now()->addMinutes($minutes),
        ]);

        $this->line(url(config('trail.path', 'trail') . '/traces?trail_token=' . $token));

        return self::SUCCESS;
    }
}
