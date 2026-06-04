<?php

namespace Trail\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;
use Trail\Models\TrailSignedLink;
use Trail\Models\TrailUser;

class AuthorizeTrailDashboard
{
    public function handle(Request $request, Closure $next): Response
    {
        $this->authorizeIp($request);

        match (config('trail.access.mode', 'trail_users')) {
            'gate' => abort_unless(Gate::allows(config('trail.access.gate', 'viewTrail')), Response::HTTP_FORBIDDEN),
            'signed_url' => $this->authorizeSignedUrl($request),
            'trail_users' => $this->authorizeTrailUser($request),
            default => null,
        };

        return $next($request);
    }

    private function authorizeIp(Request $request): void
    {
        $allowlist = config('trail.access.ip_allowlist', []);

        if ($allowlist === []) {
            return;
        }

        abort_unless(in_array($request->ip(), $allowlist, true), Response::HTTP_FORBIDDEN);
    }

    private function authorizeSignedUrl(Request $request): void
    {
        $token = $request->query('trail_token');

        abort_unless($token, Response::HTTP_FORBIDDEN);

        $exists = TrailSignedLink::query()
            ->where('token_hash', hash('sha256', (string) $token))
            ->where('expires_at', '>', now())
            ->exists();

        abort_unless($exists, Response::HTTP_FORBIDDEN);
    }

    private function authorizeTrailUser(Request $request): void
    {
        $userId = $request->session()->get('trail_user_id');

        if (! $userId || ! TrailUser::query()->whereKey($userId)->exists()) {
            abort(redirect(config('trail.path', 'trail') . '/login'));
        }
    }
}
