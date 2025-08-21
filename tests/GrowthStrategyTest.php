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

class GrowthStrategyTest extends TestCase
{
    /**
     * @throws ReflectionException
     *
     * @test
     */
    public function linear_growth_strategy(): void
    {
        Config::set('rate-limiting.max_suspension_time', 3600);

        // Linear growth: 60 * (attempts + 1)
        $this->assertEquals(60, $this->callCalculateDecayTime(0, 'linear'));
        $this->assertEquals(120, $this->callCalculateDecayTime(1, 'linear'));
        $this->assertEquals(180, $this->callCalculateDecayTime(2, 'linear'));
        $this->assertEquals(240, $this->callCalculateDecayTime(3, 'linear'));
    }

    /**
     * @throws ReflectionException
     *
     * @test
     */
    public function exponential_growth_strategy(): void
    {
        Config::set('rate-limiting.max_suspension_time', 3600);

        // Exponential growth: 60 * 2^attempts
        $this->assertEquals(60, $this->callCalculateDecayTime(0, 'exponential'));
        $this->assertEquals(120, $this->callCalculateDecayTime(1, 'exponential'));
        $this->assertEquals(240, $this->callCalculateDecayTime(2, 'exponential'));
        $this->assertEquals(480, $this->callCalculateDecayTime(3, 'exponential'));
        $this->assertEquals(960, $this->callCalculateDecayTime(4, 'exponential'));
    }

    /**
     * @throws ReflectionException
     *
     * @test
     */
    public function fibonacci_growth_strategy(): void
    {
        Config::set('rate-limiting.max_suspension_time', 3600);

        // Fibonacci growth: 60 * fibonacci(attempts + 1)
        $this->assertEquals(60, $this->callCalculateDecayTime(0, 'fibonacci')); // 60 * 1
        $this->assertEquals(120, $this->callCalculateDecayTime(1, 'fibonacci')); // 60 * 2
        $this->assertEquals(180, $this->callCalculateDecayTime(2, 'fibonacci')); // 60 * 3
        $this->assertEquals(300, $this->callCalculateDecayTime(3, 'fibonacci')); // 60 * 5
        $this->assertEquals(480, $this->callCalculateDecayTime(4, 'fibonacci')); // 60 * 8
        $this->assertEquals(780, $this->callCalculateDecayTime(5, 'fibonacci')); // 60 * 13
    }

    /**
     * @throws ReflectionException
     *
     * @test
     */
    public function unknown_growth_strategy_defaults_to_linear(): void
    {
        Config::set('rate-limiting.max_suspension_time', 3600);

        // Unknown strategy should default to linear
        $this->assertEquals(60, $this->callCalculateDecayTime(0, 'unknown'));
        $this->assertEquals(120, $this->callCalculateDecayTime(1, 'unknown'));
        $this->assertEquals(180, $this->callCalculateDecayTime(2, 'unknown'));
    }

    /**
     * @throws ReflectionException
     *
     * @test
     */
    public function max_suspension_time_is_respected(): void
    {
        Config::set('rate-limiting.max_suspension_time', 300); // 5 minutes max

        // Exponential growth should be capped at max suspension time
        $this->assertEquals(300, $this->callCalculateDecayTime(10, 'exponential')); // Would be 60 * 2^10 = 61440, but capped at 300
        $this->assertEquals(300, $this->callCalculateDecayTime(5, 'exponential')); // Would be 60 * 2^5 = 1920, but capped at 300
    }

    /**
     * @throws ReflectionException
     *
     * @test
     */
    public function fibonacci_sequence_calculation(): void
    {
        // Test Fibonacci sequence: 1, 2, 3, 5, 8, 13, 21, 34, 55, 89...
        $this->assertEquals(1, $this->callGetFibonacci(0));
        $this->assertEquals(1, $this->callGetFibonacci(1));
        $this->assertEquals(2, $this->callGetFibonacci(2));
        $this->assertEquals(3, $this->callGetFibonacci(3));
        $this->assertEquals(5, $this->callGetFibonacci(4));
        $this->assertEquals(8, $this->callGetFibonacci(5));
        $this->assertEquals(13, $this->callGetFibonacci(6));
        $this->assertEquals(21, $this->callGetFibonacci(7));
        $this->assertEquals(34, $this->callGetFibonacci(8));
        $this->assertEquals(55, $this->callGetFibonacci(9));
        $this->assertEquals(89, $this->callGetFibonacci(10));
    }

    /**
     * @throws ReflectionException
     *
     * @test
     */
    public function fibonacci_with_negative_input(): void
    {
        // Negative input should return 1
        $this->assertEquals(1, $this->callGetFibonacci(-1));
        $this->assertEquals(1, $this->callGetFibonacci(-5));
    }

    /**
     * @test
     */
    public function growth_strategies_in_actual_rate_limiting(): void
    {
        // Test that different growth strategies actually affect rate limiting behavior
        Config::set('rate-limiting.limiters.register.limits.email.max_attempts', 1);

        // Test linear growth
        Config::set('rate-limiting.limiters.register.growth_strategy', 'linear');
        $request = Request::create('/register', 'POST', ['email' => 'linear@example.com']);
        $request->setTrustedProxies(['127.0.0.1'], RequestAlias::HEADER_X_FORWARDED_FOR);

        $limiter = RateLimiter::limiter('register');
        $this->assertNull($limiter($request)); // First attempt allowed
        $result = $limiter($request); // Second attempt blocked
        $this->assertNotNull($result);

        // Test exponential growth
        Config::set('rate-limiting.limiters.register.growth_strategy', 'exponential');
        $request2 = Request::create('/register', 'POST', ['email' => 'exponential@example.com']);
        $request2->setTrustedProxies(['127.0.0.1'], RequestAlias::HEADER_X_FORWARDED_FOR);

        $this->assertNull($limiter($request2)); // First attempt allowed
        $result2 = $limiter($request2); // Second attempt blocked
        $this->assertNotNull($result2);

        // Test fibonacci growth
        Config::set('rate-limiting.limiters.register.growth_strategy', 'fibonacci');
        $request3 = Request::create('/register', 'POST', ['email' => 'fibonacci@example.com']);
        $request3->setTrustedProxies(['127.0.0.1'], RequestAlias::HEADER_X_FORWARDED_FOR);

        $this->assertNull($limiter($request3)); // First attempt allowed
        $result3 = $limiter($request3); // Second attempt blocked
        $this->assertNotNull($result3);
    }

    /**
     * @throws ReflectionException
     *
     * @test
     */
    public function max_suspension_time_configuration(): void
    {
        // Test default max suspension time
        Config::set('rate-limiting.max_suspension_time', 3600);
        $this->assertEquals(3600 / 60, $this->callCalculateDecayTime(0, 'linear')); // Should use default 3600

        // Test custom max suspension time
        Config::set('rate-limiting.max_suspension_time', 1800);
        $result = $this->callCalculateDecayTime(100, 'exponential'); // Very high attempts
        $this->assertEquals(1800, $result); // Should be capped at 1800
    }

    /**
     * @throws ReflectionException
     */
    private function callCalculateDecayTime(int $attempts, string $growthStrategy): int
    {
        $provider = new RateLimitingServiceProvider($this->app);
        $reflection = new ReflectionClass($provider);
        $method = $reflection->getMethod('calculateDecayTime');

        return $method->invoke($provider, $attempts, $growthStrategy);
    }

    /**
     * @throws ReflectionException
     */
    private function callGetFibonacci(int $n): int
    {
        $provider = new RateLimitingServiceProvider($this->app);
        $reflection = new ReflectionClass($provider);
        $method = $reflection->getMethod('getFibonacci');

        return $method->invoke($provider, $n);
    }
}
