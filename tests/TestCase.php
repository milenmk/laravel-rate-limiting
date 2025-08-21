<?php

declare(strict_types=1);

namespace Milenmk\LaravelRateLimiting\Tests;

use Milenmk\LaravelRateLimiting\Providers\RateLimitingServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            RateLimitingServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
    }
}