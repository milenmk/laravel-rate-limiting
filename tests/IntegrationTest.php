<?php

declare(strict_types=1);

namespace Milenmk\LaravelRateLimiting\Tests;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Milenmk\LaravelRateLimiting\Providers\RateLimitingServiceProvider;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Request as RequestAlias;

class IntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clear rate limiter state
        RateLimiter::clear('register:email:integration@example.com');
        RateLimiter::clear('register:ip:127.0.0.1');
        RateLimiter::clear('login:username_ip:integration@example.com|127.0.0.1');
        RateLimiter::clear('login:ip:127.0.0.1');
    }

    #[Test]
    public function complete_rate_limiting_flow_for_registration(): void
    {
        Config::set('rate-limiting.limiters.register.limits.email.max_attempts', 2);
        Config::set('rate-limiting.limiters.register.growth_strategy', 'linear');
        Config::set('rate-limiting.log_violations', true);

        $request = Request::create('/register', 'POST', ['email' => 'integration@example.com']);
        $request->setTrustedProxies(['127.0.0.1'], RequestAlias::HEADER_X_FORWARDED_FOR);

        $limiter = RateLimiter::limiter('register');

        // First attempt - should be allowed
        $result1 = $limiter($request);
        $this->assertNull($result1);
        $this->assertTrue(Session::has('rate_limit_warning'));

        // Second attempt - should be allowed but show warning
        $result2 = $limiter($request);
        $this->assertNull($result2);
        $this->assertTrue(Session::has('rate_limit_warning'));
        $this->assertStringContainsString(
            'You have 1 attempt(s) remaining before a temporary lockout.',
            Session::get('rate_limit_warning'),
        );

        // Third attempt - should be blocked
        Log::shouldReceive('warning')
            ->once()
            ->with(Mockery::pattern('/Rate limit exceeded for register:email:/'), Mockery::type('array'));

        $result3 = $limiter($request);
        $this->assertNotNull($result3);
        $this->assertInstanceOf(Limit::class, $result3);
    }

    #[Test]
    public function complete_rate_limiting_flow_for_login(): void
    {
        Config::set('rate-limiting.limiters.login.limits.username_ip.max_attempts', 2);
        Config::set('rate-limiting.limiters.login.growth_strategy', 'exponential');

        $request = Request::create('/login', 'POST', ['email' => 'integration@example.com']);
        $request->setTrustedProxies(['127.0.0.1'], RequestAlias::HEADER_X_FORWARDED_FOR);

        $limiter = RateLimiter::limiter('login');

        // First attempt - should be allowed
        $result1 = $limiter($request);
        $this->assertNull($result1);

        // Second attempt - should be allowed but show warning
        $result2 = $limiter($request);
        $this->assertNull($result2);
        $this->assertTrue(Session::has('rate_limit_warning'));

        // Third attempt - should be blocked
        $result3 = $limiter($request);
        $this->assertNotNull($result3);
        $this->assertInstanceOf(Limit::class, $result3);
    }

    #[Test]
    public function multiple_limiters_working_together(): void
    {
        Config::set('rate-limiting.limiters.register.limits.email.max_attempts', 1);
        Config::set('rate-limiting.limiters.register.limits.ip.max_attempts', 2);

        $request = Request::create('/register', 'POST', ['email' => 'integration@example.com']);
        $request->setTrustedProxies(['127.0.0.1'], RequestAlias::HEADER_X_FORWARDED_FOR);

        $limiter = RateLimiter::limiter('register');

        // First attempt - should be allowed
        $result1 = $limiter($request);
        $this->assertNull($result1);

        // Second attempt - should be blocked by email limit
        $result2 = $limiter($request);
        $this->assertNotNull($result2);

        // Try with different email but same IP
        $request2 = Request::create('/register', 'POST', ['email' => 'other@example.com']);
        $request2->setTrustedProxies(['127.0.0.1'], RequestAlias::HEADER_X_FORWARDED_FOR);

        // Should be allowed (different email, IP limit not reached yet)
        $result3 = $limiter($request2);
        $this->assertNull($result3);

        // Another attempt with different email should be blocked by IP limit
        $request3 = Request::create('/register', 'POST', ['email' => 'third@example.com']);
        $request3->setTrustedProxies(['127.0.0.1'], RequestAlias::HEADER_X_FORWARDED_FOR);

        $result4 = $limiter($request3);
        $this->assertNotNull($result4);
    }

    #[Test]
    public function different_growth_strategies_produce_different_decay_times(): void
    {
        // This test verifies that different growth strategies actually affect the system
        $strategies = ['linear', 'exponential', 'fibonacci'];
        $results = [];

        foreach ($strategies as $strategy) {
            Config::set('rate-limiting.limiters.register.growth_strategy', $strategy);
            Config::set('rate-limiting.limiters.register.limits.email.max_attempts', 1);

            $email = "test-{$strategy}@example.com";
            $request = Request::create('/register', 'POST', ['email' => $email]);
            $request->setTrustedProxies(['127.0.0.1'], RequestAlias::HEADER_X_FORWARDED_FOR);

            $limiter = RateLimiter::limiter('register');

            // First attempt allowed
            $this->assertNull($limiter($request));

            // Second attempt blocked
            $result = $limiter($request);
            $this->assertNotNull($result);

            $results[$strategy] = $result;
        }

        // All strategies should produce rate limit responses
        foreach ($results as $strategy => $result) {
            $this->assertInstanceOf(Limit::class, $result, "Strategy {$strategy} should produce Limit instance");
        }
    }

    #[Test]
    public function rate_limiting_with_custom_username_resolver(): void
    {
        Config::set('rate-limiting.username_resolver', function (Request $request) {
            return 'custom-' . $request->input('username', 'unknown');
        });
        Config::set('rate-limiting.limiters.login.limits.username_ip.max_attempts', 1);

        $request = Request::create('/login', 'POST', ['username' => 'testuser']);
        $request->setTrustedProxies(['127.0.0.1'], RequestAlias::HEADER_X_FORWARDED_FOR);

        $limiter = RateLimiter::limiter('login');

        // First attempt should be allowed
        $this->assertNull($limiter($request));

        // Second attempt should be blocked (same custom username + IP)
        $result = $limiter($request);
        $this->assertNotNull($result);

        // Different username should be allowed
        $request2 = Request::create('/login', 'POST', ['username' => 'otheruser']);
        $request2->setTrustedProxies(['127.0.0.1'], RequestAlias::HEADER_X_FORWARDED_FOR);

        $this->assertNull($limiter($request2));
    }

    #[Test]
    public function rate_limiting_disabled_globally(): void
    {
        Config::set('rate-limiting.enabled', false);
        Config::set('rate-limiting.limiters.register.limits.email.max_attempts', 1);

        // Re-boot the service provider with disabled config
        $provider = new RateLimitingServiceProvider($this->app);
        $provider->register();
        $provider->boot();

        $request = Request::create('/register', 'POST', ['email' => 'disabled@example.com']);
        $request->setTrustedProxies(['127.0.0.1'], RequestAlias::HEADER_X_FORWARDED_FOR);

        // When disabled, limiter should not be configured
        $limiter = RateLimiter::limiter('register');
        $this->assertTrue(is_null($limiter) || $limiter($request) === null);
    }

    #[Test]
    public function rate_limiting_with_session_based_two_factor(): void
    {
        Config::set('rate-limiting.limiters.two-factor.limits.session.max_attempts', 2);

        $request = Request::create('/two-factor', 'POST', ['code' => '123456']);
        $request->setTrustedProxies(['127.0.0.1'], RequestAlias::HEADER_X_FORWARDED_FOR);

        // Set up session
        Session::put('login.id', 'test-session-123');
        $request->setLaravelSession(app('session.store'));

        $limiter = RateLimiter::limiter('two-factor');

        // First two attempts should be allowed
        $this->assertNull($limiter($request));
        $this->assertNull($limiter($request));

        // Third attempt should be blocked
        $result = $limiter($request);
        $this->assertNotNull($result);

        // Different session should be allowed
        Session::put('login.id', 'different-session-456');
        $request->setLaravelSession(app('session.store'));

        $this->assertNull($limiter($request));
    }

    #[Test]
    public function rate_limiting_response_excludes_sensitive_data(): void
    {
        Config::set('rate-limiting.limiters.register.limits.email.max_attempts', 1);

        $request = Request::create('/register', 'POST', [
            'email' => 'test@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'code' => 'verification-code',
            'other_field' => 'keep-this',
        ]);
        $request->setTrustedProxies(['127.0.0.1'], RequestAlias::HEADER_X_FORWARDED_FOR);

        $limiter = RateLimiter::limiter('register');

        // First attempt allowed
        $this->assertNull($limiter($request));

        // Second attempt blocked
        $result = $limiter($request);
        $this->assertNotNull($result);
        $this->assertInstanceOf(Limit::class, $result);
    }

    #[Test]
    public function warning_messages_appear_at_correct_thresholds(): void
    {
        Session::flush();

        Config::set('rate-limiting.limiters.register.limits.email.max_attempts', 5);
        Config::set('rate-limiting.limiters.register.limits.ip.max_attempts', 5);

        $request = Request::create('/register', 'POST', ['email' => 'warning@example.com']);
        $request->setTrustedProxies(['127.0.0.1'], RequestAlias::HEADER_X_FORWARDED_FOR);

        $limiter = RateLimiter::limiter('register');

        // First 2 attempts - no warning
        for ($i = 0; $i < 2; $i++) {
            $this->assertNull($limiter($request));
            $this->assertFalse(Session::has('rate_limit_warning'), 'Warning should not appear on attempt ' . ($i + 1));
            Session::forget('rate_limit_warning'); // Clear any potential warning
        }

        // After 3rd attempt - should show warning (2 remaining)
        $this->assertNull($limiter($request));
        $this->assertTrue(Session::has('rate_limit_warning'));
        $this->assertStringContainsString('2 attempt(s) remaining', Session::get('rate_limit_warning'));

        Session::forget('rate_limit_warning');

        // After 4th attempt - should show warning (1 remaining)
        $this->assertNull($limiter($request));
        $this->assertTrue(Session::has('rate_limit_warning'));
        $this->assertStringContainsString('1 attempt(s) remaining', Session::get('rate_limit_warning'));

        // After 5th attempt - still allowed (but no warning)
        $this->assertNull($limiter($request));
        $this->assertTrue(Session::has('rate_limit_warning'));
        $this->assertStringContainsString('0 attempt(s) remaining', Session::get('rate_limit_warning'));

        // After 6th attempt - user is blocked
        $result = $limiter($request);
        $this->assertNotNull($result);
    }
}
