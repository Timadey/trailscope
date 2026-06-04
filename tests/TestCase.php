<?php

namespace Trail\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Trail\TrailServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            TrailServiceProvider::class,
        ];
    }
}
