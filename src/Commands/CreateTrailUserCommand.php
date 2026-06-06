<?php

namespace Trail\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Trail\Enums\TrailUserRole;
use Trail\Models\TrailUser;

class CreateTrailUserCommand extends Command
{
    protected $signature = 'trail:user {email} {--name=} {--role=support} {--password=}';

    protected $description = 'Create or update a Trail dashboard user.';

    public function handle(): int
    {
        $this->validateRole();

        $password = (string) ($this->option('password') ?: Str::password(24));

        TrailUser::query()->updateOrCreate(
            ['email' => $this->argument('email')],
            [
                'name' => $this->option('name') ?: $this->argument('email'),
                'role' => $this->option('role') ?: 'support',
                'password' => Hash::make($password),
            ],
        );

        $this->info('TrailScope user saved.');
        $this->line('Login URL: ' . $this->loginUrl());

        if (! $this->option('password')) {
            $this->line("Password: {$password}");
        }

        return self::SUCCESS;
    }

    private function validateRole(): void
    {
        validator(
            ['role' => $this->option('role')],
            ['role' => ['required', Rule::in(TrailUserRole::values())]],
        )->validate();
    }

    private function loginUrl(): string
    {
        $baseUrl = rtrim((string) config('app.url', url('/')), '/');
        $path = trim((string) config('trail.path', 'trail'), '/');

        return $baseUrl . '/' . trim($path . '/login', '/');
    }
}
