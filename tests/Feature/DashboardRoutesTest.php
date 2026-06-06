<?php

namespace Trail\Tests\Feature;

use Trail\Models\TrailTrace;
use Trail\Models\TrailUser;
use Trail\Tests\TestCase;

class DashboardRoutesTest extends TestCase
{
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    public function test_trace_index_route_loads(): void
    {
        config(['trail.access.mode' => 'gate']);
        \Illuminate\Support\Facades\Gate::define('viewTrail', fn ($user = null) => true);

        TrailTrace::query()->create(['trace_id' => 'trace-1', 'method' => 'GET', 'path' => 'test']);

        $this->get('/trail/traces')->assertOk();
    }

    public function test_trace_index_is_paginated_and_includes_logout_url(): void
    {
        config(['trail.access.mode' => 'gate']);
        \Illuminate\Support\Facades\Gate::define('viewTrail', fn ($user = null) => true);

        foreach (range(1, 30) as $index) {
            TrailTrace::query()->create([
                'trace_id' => "trace-{$index}",
                'method' => 'GET',
                'path' => "test-{$index}",
                'started_at' => now()->subMinutes($index),
            ]);
        }

        $page = $this->get('/trail/traces')->assertOk()->viewData('page');

        $this->assertCount(25, $page['props']['traces']['data']);
        $this->assertSame('/trail/logout', parse_url($page['props']['logoutUrl'], PHP_URL_PATH));
        $this->assertNotNull($page['props']['traces']['next_page_url']);
    }

    public function test_trace_index_includes_journey_url_for_owned_traces(): void
    {
        config(['trail.access.mode' => 'gate']);
        \Illuminate\Support\Facades\Gate::define('viewTrail', fn ($user = null) => true);

        TrailTrace::query()->create([
            'trace_id' => 'trace-1',
            'method' => 'GET',
            'path' => 'test',
            'owner_type' => 'user',
            'owner_id' => '1',
            'owner_label' => 'Admin',
        ]);

        $page = $this->get('/trail/traces')->assertOk()->viewData('page');

        $this->assertSame('/trail/journeys/user/1', parse_url($page['props']['traces']['data'][0]['journey_url'], PHP_URL_PATH));
    }

    public function test_trace_show_includes_logout_url(): void
    {
        config(['trail.access.mode' => 'gate']);
        \Illuminate\Support\Facades\Gate::define('viewTrail', fn ($user = null) => true);

        $trace = TrailTrace::query()->create(['trace_id' => 'trace-1', 'method' => 'GET', 'path' => 'test']);

        $page = $this->get('/trail/traces/' . $trace->id)->assertOk()->viewData('page');

        $this->assertSame('/trail/logout', parse_url($page['props']['logoutUrl'], PHP_URL_PATH));
    }

    public function test_trace_show_includes_journey_url_for_owned_trace(): void
    {
        config(['trail.access.mode' => 'gate']);
        \Illuminate\Support\Facades\Gate::define('viewTrail', fn ($user = null) => true);

        $trace = TrailTrace::query()->create([
            'trace_id' => 'trace-1',
            'method' => 'GET',
            'path' => 'test',
            'owner_type' => 'user',
            'owner_id' => '1',
            'owner_label' => 'Admin',
        ]);

        $page = $this->get('/trail/traces/' . $trace->id)->assertOk()->viewData('page');

        $this->assertSame('/trail/journeys/user/1', parse_url($page['props']['trace']['journey_url'], PHP_URL_PATH));
    }

    public function test_trace_show_exposes_response_for_technical_viewers(): void
    {
        config(['trail.access.mode' => 'gate']);
        \Illuminate\Support\Facades\Gate::define('viewTrail', fn ($user = null) => true);

        $trace = TrailTrace::query()->create([
            'trace_id' => 'trace-1',
            'method' => 'GET',
            'path' => 'test',
            'response' => ['status' => 200, 'content' => '{"ok":true}'],
        ]);

        $page = $this->get('/trail/traces/' . $trace->id)->assertOk()->viewData('page');

        $this->assertTrue($page['props']['canViewTechnicalContext']);
        $this->assertSame(['status' => 200, 'content' => '{"ok":true}'], $page['props']['trace']['response']);
    }

    public function test_user_journey_includes_logout_url(): void
    {
        config(['trail.access.mode' => 'gate']);
        \Illuminate\Support\Facades\Gate::define('viewTrail', fn ($user = null) => true);

        TrailTrace::query()->create([
            'trace_id' => 'trace-1',
            'method' => 'GET',
            'path' => 'test',
            'owner_type' => 'user',
            'owner_id' => '1',
        ]);

        $page = $this->get('/trail/journeys/user/1')->assertOk()->viewData('page');

        $this->assertSame('/trail/logout', parse_url($page['props']['logoutUrl'], PHP_URL_PATH));
    }

    public function test_invalid_login_redirects_with_validation_errors(): void
    {
        $this->from('/trail/login')
            ->post('/trail/login', [
                'email' => 'admin@example.test',
                'password' => 'wrong-password',
            ])
            ->assertRedirect('/trail/login')
            ->assertSessionHasErrors('email');
    }

    public function test_logout_clears_trail_user_session(): void
    {
        $user = TrailUser::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => bcrypt('secret-password'),
            'role' => 'admin',
        ]);

        $this->withSession(['trail_user_id' => $user->id])
            ->post('/trail/logout')
            ->assertRedirect('/trail/login')
            ->assertSessionMissing('trail_user_id');
    }
}
