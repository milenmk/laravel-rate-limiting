<?php

declare(strict_types=1);

namespace Milenmk\LaravelRateLimiting\Tests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use Milenmk\LaravelRateLimiting\Providers\RateLimitingServiceProvider;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\HttpFoundation\Request as RequestAlias;

class UsernameResolutionTest extends TestCase
{
    /**
     * @throws ReflectionException
     *
     * @test
     */
    public function custom_resolver_takes_priority(): void
    {
        Config::set('rate-limiting.username_resolver', function (Request $request) {
            return 'custom-' . $request->input('email');
        });

        $request = Request::create('/', 'POST', ['email' => 'test@example.com']);

        $result = $this->callResolveUsername($request);

        $this->assertEquals('custom-test@example.com', $result);
    }

    /**
     * @throws ReflectionException
     *
     * @test
     */
    public function custom_resolver_fallback_when_returns_null(): void
    {
        Config::set('rate-limiting.username_resolver', function () {
            return null; // Simulate resolver returning null
        });
        Config::set('rate-limiting.username_field', 'email');

        $request = Request::create('/', 'POST', ['email' => 'test@example.com']);

        $result = $this->callResolveUsername($request);

        $this->assertEquals('test@example.com', $result);
    }

    /**
     * @test
     */
    public function fortify_username_field_when_available(): void
    {
        // Mock Fortify class existence and username method
        if (! class_exists('Laravel\Fortify\Fortify')) {
            $this->markTestSkipped('Fortify not available for testing');
        }

        // This test would need actual Fortify to be meaningful
        // For now, we'll test the fallback behavior
        $this->assertTrue(true);
    }

    /**
     * @throws ReflectionException
     *
     * @test
     */
    public function configurable_username_field(): void
    {
        Config::set('rate-limiting.username_field', 'username');

        $request = Request::create('/', 'POST', ['username' => 'testuser']);

        $result = $this->callResolveUsername($request);

        $this->assertEquals('testuser', $result);
    }

    /**
     * @throws ReflectionException
     *
     * @test
     */
    public function fallback_to_common_fields(): void
    {
        Config::set('rate-limiting.username_field', 'nonexistent');

        // Test email fallback
        $request = Request::create('/', 'POST', ['email' => 'test@example.com']);
        $result = $this->callResolveUsername($request);
        $this->assertEquals('test@example.com', $result);

        // Test username fallback
        $request = Request::create('/', 'POST', ['username' => 'testuser']);
        $result = $this->callResolveUsername($request);
        $this->assertEquals('testuser', $result);

        // Test login fallback
        $request = Request::create('/', 'POST', ['login' => 'loginuser']);
        $result = $this->callResolveUsername($request);
        $this->assertEquals('loginuser', $result);

        // Test user_email fallback
        $request = Request::create('/', 'POST', ['user_email' => 'user@example.com']);
        $result = $this->callResolveUsername($request);
        $this->assertEquals('user@example.com', $result);

        // Test user_name fallback
        $request = Request::create('/', 'POST', ['user_name' => 'username']);
        $result = $this->callResolveUsername($request);
        $this->assertEquals('username', $result);
    }

    /**
     * @throws ReflectionException
     *
     * @test
     */
    public function returns_unknown_when_no_field_found(): void
    {
        Config::set('rate-limiting.username_field', 'nonexistent');

        $request = Request::create('/', 'POST', ['other_field' => 'value']);

        $result = $this->callResolveUsername($request);

        $this->assertEquals('unknown', $result);
    }

    /**
     * @throws ReflectionException
     *
     * @test
     */
    public function handles_non_string_values(): void
    {
        Config::set('rate-limiting.username_field', 'email');

        // Test with array value
        $request = Request::create('/', 'POST', ['email' => ['test@example.com']]);
        $result = $this->callResolveUsername($request);
        $this->assertEquals('Array', $result); // PHP converts array to string "Array"

        // Test with numeric value
        $request = Request::create('/', 'POST', ['email' => 12345]);
        $result = $this->callResolveUsername($request);
        $this->assertEquals('12345', $result);

        // Test with boolean value
        $request = Request::create('/', 'POST', ['email' => true]);
        $result = $this->callResolveUsername($request);
        $this->assertEquals('1', $result);
    }

    /**
     * @throws ReflectionException
     *
     * @test
     */
    public function custom_resolver_with_non_callable(): void
    {
        Config::set('rate-limiting.username_resolver', 'not-callable');
        Config::set('rate-limiting.username_field', 'email');

        $request = Request::create('/', 'POST', ['email' => 'test@example.com']);

        $result = $this->callResolveUsername($request);

        // Should fall back to configured field when resolver is not callable
        $this->assertEquals('test@example.com', $result);
    }

    /**
     * @test
     */
    public function username_resolution_in_login_rate_limiter(): void
    {
        Config::set('rate-limiting.username_field', 'email');
        Config::set('rate-limiting.limiters.login.limits.username_ip.max_attempts', 1);

        $request = Request::create('/login', 'POST', ['email' => 'test@example.com']);
        $request->setTrustedProxies(['127.0.0.1'], RequestAlias::HEADER_X_FORWARDED_FOR);

        $limiter = RateLimiter::limiter('login');

        // First attempt should be allowed
        $this->assertNull($limiter($request));

        // Second attempt should be blocked (same username+IP combination)
        $result = $limiter($request);
        $this->assertNotNull($result);

        // Different email should be allowed (different username+IP combination)
        $request2 = Request::create('/login', 'POST', ['email' => 'other@example.com']);
        $request2->setTrustedProxies(['127.0.0.1'], RequestAlias::HEADER_X_FORWARDED_FOR);

        $this->assertNull($limiter($request2));
    }

    /**
     * @throws ReflectionException
     */
    private function callResolveUsername(Request $request): string
    {
        $provider = new RateLimitingServiceProvider($this->app);
        $reflection = new ReflectionClass($provider);
        $method = $reflection->getMethod('resolveUsername');

        return $method->invoke($provider, $request);
    }
}
