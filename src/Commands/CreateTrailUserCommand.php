<?php

namespace Trail\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Trail\Models\TrailUser;

class CreateTrailUserCommand extends Command
{
    protected $signature = 'trail:user {email} {--name=} {--role=support} {--password=}';

    protected $description = 'Create or update a Trail dashboard user.';

    public function handle(): int
    {
        $password = (string) ($this->option('password') ?: Str::password(24));

        TrailUser::query()->updateOrCreate(
            ['email' => $this->argument('email')],
            [
                'name' => $this->option('name') ?: $this->argument('email'),
                'role' => $this->option('role') ?: 'support',
                'password' => Hash::make($password),
            ],
        );

        $this->info('Trail user saved.');

        if (! $this->option('password')) {
            $this->line("Password: {$password}");
        }

        return self::SUCCESS;
    }
}
