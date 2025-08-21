<?php

declare(strict_types=1);

namespace Milenmk\LaravelRateLimiting\Tests;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\RateLimiter;
use Milenmk\LaravelRateLimiting\Providers\RateLimitingServiceProvider;

class ServiceProviderTest extends TestCase
{
    /**
     * @test
     */
    public function service_provider_is_registered(): void
    {
        $this->assertTrue($this->app->providerIsLoaded(RateLimitingServiceProvider::class));
    }

    /**
     * @test
     */
    public function config_is_merged(): void
    {
        $this->assertTrue(Config::has('rate-limiting'));
        $this->assertTrue(Config::get('rate-limiting.enabled'));
        $this->assertEquals('linear', Config::get('rate-limiting.limiters.register.growth_strategy'));
    }

    /**
     * @test
     */
    public function config_can_be_published(): void
    {
        $configPath = config_path('rate-limiting.php');

        // Clean up if exists
        if (File::exists($configPath)) {
            File::delete($configPath);
        }

        Artisan::call('vendor:publish', [
            '--tag' => 'rate-limiting-config',
            '--force' => true,
        ]);

        $this->assertTrue(File::exists($configPath));

        // Clean up
        File::delete($configPath);
    }

    /**
     * @test
     */
    public function env_example_can_be_published(): void
    {
        $envPath = base_path('.env.rate-limiting.example');

        // Clean up if exists
        if (File::exists($envPath)) {
            File::delete($envPath);
        }

        Artisan::call('vendor:publish', [
            '--tag' => 'rate-limiting-env',
            '--force' => true,
        ]);

        $this->assertTrue(File::exists($envPath));

        // Clean up
        File::delete($envPath);
    }

    /**
     * @test
     */
    public function views_are_loaded(): void
    {
        $this->assertTrue($this->app['view']->exists('rate-limiting::components.error-message'));
        $this->assertTrue($this->app['view']->exists('rate-limiting::components.warning-message'));
    }

    /**
     * @test
     */
    public function views_can_be_published(): void
    {
        $viewsPath = resource_path('views/vendor/milenmk/laravel-rate-limiting');

        // Clean up if exists
        if (File::exists($viewsPath)) {
            File::deleteDirectory($viewsPath);
        }

        Artisan::call('vendor:publish', [
            '--tag' => 'rate-limiting-views',
            '--force' => true,
        ]);

        $this->assertTrue(File::exists($viewsPath));
        $this->assertTrue(File::exists($viewsPath . '/components/error-message.blade.php'));
        $this->assertTrue(File::exists($viewsPath . '/components/warning-message.blade.php'));

        // Clean up
        File::deleteDirectory($viewsPath);
    }

    /**
     * @test
     */
    public function blade_components_are_registered(): void
    {
        $component = Blade::getAnonymousComponentNamespace('rate-limiting::components');
        $this->assertNotNull($component);
    }

    /**
     * @test
     */
    public function rate_limiters_are_configured_when_enabled(): void
    {
        Config::set('rate-limiting.enabled', true);

        // Re-boot the service provider
        $provider = new RateLimitingServiceProvider($this->app);
        $provider->boot();

        // Check that rate limiters are configured
        $this->assertTrue(RateLimiter::limiter('register') !== null);
        $this->assertTrue(RateLimiter::limiter('login') !== null);
        $this->assertTrue(RateLimiter::limiter('forgot-password') !== null);
        $this->assertTrue(RateLimiter::limiter('two-factor') !== null);
    }

    /**
     * @test
     */
    public function rate_limiters_are_not_configured_when_disabled(): void
    {
        Config::set('rate-limiting.enabled', false);

        // Create a fresh app instance to test disabled state
        $this->app['config']->set('rate-limiting.enabled', false);

        $provider = new RateLimitingServiceProvider($this->app);
        $provider->register();
        $provider->boot();

        // Rate limiters should not be configured when disabled
        $this->assertNull(RateLimiter::limiter('register'));
    }

    /**
     * @test
     */
    public function individual_limiters_can_be_disabled(): void
    {
        Config::set('rate-limiting.limiters.register.enabled', false);
        Config::set('rate-limiting.limiters.login.enabled', true);

        // Re-boot the service provider
        $provider = new RateLimitingServiceProvider($this->app);
        $provider->boot();

        // Login should be configured, register should not affect the limiter
        $this->assertTrue(RateLimiter::limiter('login') !== null);
    }
}
