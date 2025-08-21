<?php

declare(strict_types=1);

namespace Milenmk\LaravelRateLimiting\Tests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Request as RequestAlias;

class RateLimiterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clear any existing rate limit data
        RateLimiter::clear('register:email:test@example.com');
        RateLimiter::clear('register:ip:127.0.0.1');
        RateLimiter::clear('login:username_ip:test@example.com|127.0.0.1');
        RateLimiter::clear('login:ip:127.0.0.1');
        RateLimiter::clear('forgot-password:email:test@example.com');
        RateLimiter::clear('two-factor:session:test-session');
    }

    #[Test]
    public function register_rate_limiter_allows_requests_within_limit(): void
    {
        Config::set('rate-limiting.limiters.register.limits.email.max_attempts', 3);

        $request = Request::create('/register', 'POST', ['email' => 'test@example.com']);
        $request->setTrustedProxies(['127.0.0.1'], RequestAlias::HEADER_X_FORWARDED_FOR);

        $limiter = RateLimiter::limiter('register');

        // First 3 attempts should be allowed
        $this->assertNull($limiter($request));
        $this->assertNull($limiter($request));
        $this->assertNull($limiter($request));
    }

    #[Test]
    public function register_rate_limiter_blocks_requests_over_limit(): void
    {
        Config::set('rate-limiting.limiters.register.limits.email.max_attempts', 2);

        $request = Request::create('/register', 'POST', ['email' => 'test@example.com']);
        $request->setTrustedProxies(['127.0.0.1'], RequestAlias::HEADER_X_FORWARDED_FOR);

        $limiter = RateLimiter::limiter('register');

        // First 2 attempts should be allowed
        $this->assertNull($limiter($request));
        $this->assertNull($limiter($request));

        // Third attempt should be blocked
        $result = $limiter($request);
        $this->assertNotNull($result);
    }

    #[Test]
    public function login_rate_limiter_with_username_ip(): void
    {
        Config::set('rate-limiting.limiters.login.limits.username_ip.max_attempts', 2);

        $request = Request::create('/login', 'POST', ['email' => 'test@example.com']);
        $request->setTrustedProxies(['127.0.0.1'], RequestAlias::HEADER_X_FORWARDED_FOR);

        $limiter = RateLimiter::limiter('login');

        // First 2 attempts should be allowed
        $this->assertNull($limiter($request));
        $this->assertNull($limiter($request));

        // Third attempt should be blocked
        $result = $limiter($request);
        $this->assertNotNull($result);
    }

    #[Test]
    public function forgot_password_rate_limiter(): void
    {
        Config::set('rate-limiting.limiters.forgot-password.limits.email.max_attempts', 2);

        $request = Request::create('/forgot-password', 'POST', ['email' => 'test@example.com']);
        $request->setTrustedProxies(['127.0.0.1'], RequestAlias::HEADER_X_FORWARDED_FOR);

        $limiter = RateLimiter::limiter('forgot-password');

        // First 2 attempts should be allowed
        $this->assertNull($limiter($request));
        $this->assertNull($limiter($request));

        // Third attempt should be blocked
        $result = $limiter($request);
        $this->assertNotNull($result);
    }

    #[Test]
    public function two_factor_rate_limiter_with_session(): void
    {
        Config::set('rate-limiting.limiters.two-factor.limits.session.max_attempts', 2);

        $request = Request::create('/two-factor', 'POST');
        $request->setTrustedProxies(['127.0.0.1'], RequestAlias::HEADER_X_FORWARDED_FOR);

        // Mock session
        Session::put('login.id', 'test-session');
        $request->setLaravelSession(app('session.store'));

        $limiter = RateLimiter::limiter('two-factor');

        // First 2 attempts should be allowed
        $this->assertNull($limiter($request));
        $this->assertNull($limiter($request));

        // Third attempt should be blocked
        $result = $limiter($request);
        $this->assertNotNull($result);
    }

    #[Test]
    public function rate_limiter_logs_violations_when_enabled(): void
    {
        Config::set('rate-limiting.log_violations', true);
        Config::set('rate-limiting.limiters.register.limits.email.max_attempts', 1);

        Log::shouldReceive('warning')
            ->once()
            ->with(Mockery::pattern('/Rate limit exceeded for register:email:/'), Mockery::type('array'));

        $request = Request::create('/register', 'POST', ['email' => 'test@example.com']);
        $request->setTrustedProxies(['127.0.0.1'], RequestAlias::HEADER_X_FORWARDED_FOR);

        $limiter = RateLimiter::limiter('register');

        // First attempt allowed
        $this->assertNull($limiter($request));

        // Second attempt should be blocked and logged
        $result = $limiter($request);
        $this->assertNotNull($result);
    }

    #[Test]
    public function rate_limiter_does_not_log_when_disabled(): void
    {
        Config::set('rate-limiting.log_violations', false);
        Config::set('rate-limiting.limiters.register.limits.email.max_attempts', 1);

        Log::shouldReceive('warning')->never();

        $request = Request::create('/register', 'POST', ['email' => 'test@example.com']);
        $request->setTrustedProxies(['127.0.0.1'], RequestAlias::HEADER_X_FORWARDED_FOR);

        $limiter = RateLimiter::limiter('register');

        // First attempt allowed
        $this->assertNull($limiter($request));

        // Second attempt should be blocked but not logged
        $result = $limiter($request);
        $this->assertNotNull($result);
    }

    #[Test]
    public function rate_limiter_shows_warning_when_approaching_limit(): void
    {
        Config::set('rate-limiting.limiters.register.limits.email.max_attempts', 4);
        Config::set('rate-limiting.limiters.register.limits.ip.max_attempts', 4);

        $request = Request::create('/register', 'POST', ['email' => 'test@example.com']);
        $request->setTrustedProxies(['127.0.0.1'], RequestAlias::HEADER_X_FORWARDED_FOR);

        $limiter = RateLimiter::limiter('register');

        // First attempt - no warning
        $this->assertNull($limiter($request));
        $this->assertFalse(Session::has('rate_limit_warning'));

        // Second attempt - should show warning (2 remaining)
        $this->assertNull($limiter($request));
        $this->assertTrue(Session::has('rate_limit_warning'));
        $this->assertStringContainsString('2 attempt(s) remaining', Session::get('rate_limit_warning'));

        // Third attempt - should show warning (1 remaining)
        $this->assertNull($limiter($request));
        $this->assertTrue(Session::has('rate_limit_warning'));
        $this->assertStringContainsString('1 attempt(s) remaining', Session::get('rate_limit_warning'));

        // Fourth attempt - should show warning (0 remaining)
        $this->assertNull($limiter($request));
        $this->assertTrue(Session::has('rate_limit_warning'));
        $this->assertStringContainsString('0 attempt(s) remaining', Session::get('rate_limit_warning'));
    }

    #[Test]
    public function disabled_limit_types_are_ignored(): void
    {
        Config::set('rate-limiting.limiters.register.limits.email.enabled', false);
        Config::set('rate-limiting.limiters.register.limits.ip.enabled', true);
        Config::set('rate-limiting.limiters.register.limits.ip.max_attempts', 1);

        $request = Request::create('/register', 'POST', ['email' => 'test@example.com']);
        $request->setTrustedProxies(['127.0.0.1'], RequestAlias::HEADER_X_FORWARDED_FOR);

        $limiter = RateLimiter::limiter('register');

        // Email limit is disabled, so multiple attempts with same email should work
        // until IP limit is hit
        $this->assertNull($limiter($request));

        // Second attempt should be blocked by IP limit
        $result = $limiter($request);
        $this->assertNotNull($result);
    }

    #[Test]
    public function global_limits_work(): void
    {
        Config::set('rate-limiting.limiters.register.limits.global.enabled', true);
        Config::set('rate-limiting.limiters.register.limits.global.max_attempts', 1);
        Config::set('rate-limiting.limiters.register.limits.email.enabled', false);
        Config::set('rate-limiting.limiters.register.limits.ip.enabled', false);

        $request1 = Request::create('/register', 'POST', ['email' => 'test1@example.com']);
        $request1->setTrustedProxies(['127.0.0.1'], RequestAlias::HEADER_X_FORWARDED_FOR);

        $request2 = Request::create('/register', 'POST', ['email' => 'test2@example.com']);
        $request2->setTrustedProxies(['192.168.1.1'], RequestAlias::HEADER_X_FORWARDED_FOR);

        $limiter = RateLimiter::limiter('register');

        // First global attempt should be allowed
        $this->assertNull($limiter($request1));

        // Second global attempt (different email, different IP) should be blocked
        $result = $limiter($request2);
        $this->assertNotNull($result);
    }
}
