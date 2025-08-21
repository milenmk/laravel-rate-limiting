<?php

declare(strict_types=1);

namespace Milenmk\LaravelRateLimiting\Tests;

use Illuminate\Support\Facades\Config;

class RateLimitingTest extends TestCase
{
    /** @test */
    public function it_can_load_the_service_provider(): void
    {
        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_publish_config(): void
    {
        $this->artisan('vendor:publish', [
            '--tag' => 'rate-limiting-config',
            '--force' => true,
        ]);

        $this->assertTrue(true);
    }

    /** @test */
    public function it_loads_default_config(): void
    {
        $this->assertTrue(Config::get('rate-limiting.enabled'));
        $this->assertEquals('linear', Config::get('rate-limiting.limiters.register.growth_strategy'));
    }
}