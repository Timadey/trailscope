<?php

namespace Trail\Tests\Feature;

use Illuminate\Support\Facades\Hash;
use Trail\Models\TrailUser;
use Trail\Tests\TestCase;

class TrailUserAccessTest extends TestCase
{
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    public function test_trail_user_can_be_created_by_command(): void
    {
        $this->artisan('trail:user', [
            'email' => 'admin@example.test',
            '--name' => 'Admin',
            '--role' => 'admin',
            '--password' => 'secret-password',
        ])->assertExitCode(0);

        $user = TrailUser::query()->where('email', 'admin@example.test')->firstOrFail();

        $this->assertSame('admin', $user->role);
        $this->assertTrue(Hash::check('secret-password', $user->password));
    }
}
