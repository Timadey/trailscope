<?php

namespace Trail\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Trail\TrailServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    protected function getPackageProviders($app): array
    {
        return [
            TrailServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:' . base64_encode(str_repeat('a', 32)));
        $app['config']->set('trail.enabled', true);
    }
}
