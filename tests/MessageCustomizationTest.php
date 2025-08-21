<?php

declare(strict_types=1);

namespace Milenmk\LaravelRateLimiting\Tests;

use Illuminate\Support\Facades\Config;
use Milenmk\LaravelRateLimiting\Providers\RateLimitingServiceProvider;
use ReflectionClass;
use ReflectionException;

class MessageCustomizationTest extends TestCase
{
    /**
     * @throws ReflectionException
     *
     * @test
     */
    public function custom_rate_limit_messages(): void
    {
        // Test register email message
        $message = $this->callGetRateLimitMessage('register', 'email', 120, 3);
        $this->assertStringContainsString('Too many registration attempts with this email address', $message);
        $this->assertStringContainsString('2 minutes', $message);

        // Test login username_ip message
        $message = $this->callGetRateLimitMessage('login', 'username_ip', 300, 5);
        $this->assertStringContainsString('Too many login attempts with this username from your location', $message);
        $this->assertStringContainsString('5 minutes', $message);

        // Test forgot-password ip message
        $message = $this->callGetRateLimitMessage('forgot-password', 'ip', 180, 2);
        $this->assertStringContainsString('Too many password reset attempts from your location', $message);
        $this->assertStringContainsString('3 minutes', $message);

        // Test two-factor session message
        $message = $this->callGetRateLimitMessage('two-factor', 'session', 240, 4);
        $this->assertStringContainsString('Too many two-factor authentication attempts for this session', $message);
        $this->assertStringContainsString('4 minutes', $message);
    }

    /**
     * @throws ReflectionException
     *
     * @test
     */
    public function default_message_fallback(): void
    {
        // Test with non-existent limiter type
        $message = $this->callGetRateLimitMessage('nonexistent', 'email', 60, 1);
        $this->assertStringContainsString('Too many attempts', $message);
        $this->assertStringContainsString('1 minute', $message);

        // Test with non-existent limit type
        $message = $this->callGetRateLimitMessage('register', 'nonexistent', 120, 2);
        $this->assertStringContainsString('Too many attempts', $message);
        $this->assertStringContainsString('2 minutes', $message);
    }

    /**
     * @throws ReflectionException
     *
     * @test
     */
    public function wait_time_calculation_in_messages(): void
    {
        // Test seconds to minutes conversion
        $message = $this->callGetRateLimitMessage('register', 'email', 59, 1);
        $this->assertStringContainsString('1 minute', $message); // 59 seconds should round up to 1 minute

        $message = $this->callGetRateLimitMessage('register', 'email', 61, 1);
        $this->assertStringContainsString('2 minutes', $message); // 61 seconds should round up to 2 minutes

        $message = $this->callGetRateLimitMessage('register', 'email', 120, 1);
        $this->assertStringContainsString('2 minutes', $message); // Exactly 2 minutes

        $message = $this->callGetRateLimitMessage('register', 'email', 3600, 1);
        $this->assertStringContainsString('60 minutes', $message); // 1 hour = 60 minutes
    }

    /**
     * @throws ReflectionException
     *
     * @test
     */
    public function suggestions_for_login_limiter(): void
    {
        // Test low attempts suggestion
        $suggestion = $this->callGetSuggestions('login', 2);
        $this->assertStringContainsString('double-check your email and password', $suggestion);

        // Test high attempts suggestion
        $suggestion = $this->callGetSuggestions('login', 5);
        $this->assertStringContainsString('Consider resetting your password', $suggestion);
    }

    /**
     * @throws ReflectionException
     *
     * @test
     */
    public function suggestions_for_two_factor_limiter(): void
    {
        // Test low attempts suggestion
        $suggestion = $this->callGetSuggestions('two-factor', 2);
        $this->assertStringContainsString('check your authenticator app', $suggestion);

        // Test high attempts suggestion
        $suggestion = $this->callGetSuggestions('two-factor', 4);
        $this->assertStringContainsString('try using a recovery code', $suggestion);
    }

    /**
     * @throws ReflectionException
     *
     * @test
     */
    public function suggestions_for_other_limiters(): void
    {
        // Test register suggestion
        $suggestion = $this->callGetSuggestions('register', 3);
        $this->assertStringContainsString('verify all required fields', $suggestion);

        // Test forgot-password suggestion
        $suggestion = $this->callGetSuggestions('forgot-password', 2);
        $this->assertStringContainsString('ensure the email address is correct', $suggestion);
    }

    /**
     * @throws ReflectionException
     *
     * @test
     */
    public function default_suggestion_fallback(): void
    {
        $suggestion = $this->callGetSuggestions('nonexistent', 3);
        $this->assertStringContainsString('Please wait before trying again', $suggestion);
    }

    /**
     * @throws ReflectionException
     *
     * @test
     */
    public function warning_messages(): void
    {
        // Test login warning
        $warning = $this->callGetWarningMessage('login', 2);
        $this->assertStringContainsString('2 attempt(s) remaining', $warning);
        $this->assertStringContainsString('forgotten your password', $warning);

        // Test register warning
        $warning = $this->callGetWarningMessage('register', 1);
        $this->assertStringContainsString('1 attempt(s) remaining', $warning);
        $this->assertStringContainsString('double-check your information', $warning);

        // Test forgot-password warning
        $warning = $this->callGetWarningMessage('forgot-password', 2);
        $this->assertStringContainsString('2 attempt(s) remaining', $warning);
        $this->assertStringContainsString('verify the email address', $warning);

        // Test two-factor warning
        $warning = $this->callGetWarningMessage('two-factor', 1);
        $this->assertStringContainsString('1 attempt(s) remaining', $warning);
        $this->assertStringContainsString('Double-check your authenticator', $warning);
    }

    /**
     * @throws ReflectionException
     *
     * @test
     */
    public function default_warning_fallback(): void
    {
        $warning = $this->callGetWarningMessage('nonexistent', 2);
        $this->assertStringContainsString('2 attempt(s) remaining', $warning);
        $this->assertStringContainsString('verify your information', $warning);
    }

    /**
     * @throws ReflectionException
     *
     * @test
     */
    public function custom_message_configuration(): void
    {
        // Test custom message override
        Config::set('rate-limiting.messages.register.email', 'Custom registration message: wait :minutes minutes');

        $message = $this->callGetRateLimitMessage('register', 'email', 180, 3);
        $this->assertStringContainsString('Custom registration message: wait 3 minutes', $message);
    }

    /**
     * @throws ReflectionException
     *
     * @test
     */
    public function custom_suggestion_configuration(): void
    {
        // Test custom suggestion override
        Config::set('rate-limiting.suggestions.register', 'Custom registration suggestion');

        $suggestion = $this->callGetSuggestions('register', 3);
        $this->assertStringContainsString('Custom registration suggestion', $suggestion);
    }

    /**
     * @throws ReflectionException
     *
     * @test
     */
    public function custom_warning_configuration(): void
    {
        // Test custom warning base message
        Config::set('rate-limiting.warning_messages.base', 'Custom warning: :attempts left');

        $warning = $this->callGetWarningMessage('login', 2);
        $this->assertStringContainsString('Custom warning: 2 left', $warning);

        // Test custom warning suggestion
        Config::set('rate-limiting.warning_messages.suggestions.login', 'Custom login warning suggestion');

        $warning = $this->callGetWarningMessage('login', 1);
        $this->assertStringContainsString('Custom login warning suggestion', $warning);
    }

    /**
     * @throws ReflectionException
     *
     * @test
     */
    public function message_includes_suggestions(): void
    {
        $message = $this->callGetRateLimitMessage('login', 'username_ip', 120, 5);

        // Should contain both the main message and the suggestion
        $this->assertStringContainsString('Too many login attempts', $message);
        $this->assertStringContainsString('Consider resetting your password', $message);
    }

    /**
     * @throws ReflectionException
     *
     * @test
     */
    public function empty_suggestion_handling(): void
    {
        // Set empty suggestion
        Config::set('rate-limiting.suggestions.register', '');

        $message = $this->callGetRateLimitMessage('register', 'email', 120, 3);

        // Should still contain the main message
        $this->assertStringContainsString('Too many registration attempts', $message);
        // Should not have extra spaces from empty suggestion
        $this->assertStringNotContainsString('  ', $message);
    }

    /**
     * @throws ReflectionException
     */
    private function callGetRateLimitMessage(
        string $limiterType,
        string $limitType,
        int $waitSeconds,
        int $attempts,
    ): string {
        $provider = new RateLimitingServiceProvider($this->app);
        $reflection = new ReflectionClass($provider);
        $method = $reflection->getMethod('getRateLimitMessage');

        return $method->invoke($provider, $limiterType, $limitType, $waitSeconds, $attempts);
    }

    /**
     * @throws ReflectionException
     */
    private function callGetSuggestions(string $limiterType, int $attempts): string
    {
        $provider = new RateLimitingServiceProvider($this->app);
        $reflection = new ReflectionClass($provider);
        $method = $reflection->getMethod('getSuggestions');

        return $method->invoke($provider, $limiterType, $attempts);
    }

    /**
     * @throws ReflectionException
     */
    private function callGetWarningMessage(string $limiterType, int $remainingAttempts): string
    {
        $provider = new RateLimitingServiceProvider($this->app);
        $reflection = new ReflectionClass($provider);
        $method = $reflection->getMethod('getWarningMessage');

        return $method->invoke($provider, $limiterType, $remainingAttempts);
    }
}
