<?php

namespace Trail\Tests\Feature;

use Illuminate\Support\Facades\Hash;
use Trail\Models\TrailUser;
use Trail\Tests\TestCase;

class TrailUserLoginTest extends TestCase
{
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    public function test_trail_users_must_log_in_to_view_dashboard(): void
    {
        config(['trail.access.mode' => 'trail_users']);

        $this->get('/trail/traces')->assertRedirect('/trail/login');
    }

    public function test_login_page_loads_package_dashboard_assets(): void
    {
        $this->withVite();

        $this->get('/trail/login')
            ->assertOk()
            ->assertSee('/trail/assets/app-', false);
    }

    public function test_trail_user_can_log_in(): void
    {
        config(['trail.access.mode' => 'trail_users']);

        TrailUser::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => Hash::make('secret-password'),
            'role' => 'admin',
        ]);

        $this->post('/trail/login', [
            'email' => 'admin@example.test',
            'password' => 'secret-password',
        ])->assertRedirect('/trail/traces');

        $this->assertSame(1, session('trail_user_id'));
    }
}
