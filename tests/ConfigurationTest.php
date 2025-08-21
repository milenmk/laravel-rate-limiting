<?php

declare(strict_types=1);

namespace Milenmk\LaravelRateLimiting\Tests;

use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;

class ConfigurationTest extends TestCase
{
    #[Test]
    public function default_configuration_values(): void
    {
        // Test global settings
        $this->assertTrue(Config::get('rate-limiting.enabled'));
        $this->assertTrue(Config::get('rate-limiting.log_violations'));
        $this->assertEquals(3600, Config::get('rate-limiting.max_suspension_time'));

        // Test username resolution
        $this->assertEquals('email', Config::get('rate-limiting.username_field'));
        $this->assertNull(Config::get('rate-limiting.username_resolver'));
    }

    #[Test]
    public function register_limiter_configuration(): void
    {
        $registerConfig = Config::get('rate-limiting.limiters.register');

        $this->assertTrue($registerConfig['enabled']);
        $this->assertEquals('linear', $registerConfig['growth_strategy']);

        // Test limits
        $this->assertTrue($registerConfig['limits']['global']['enabled']);
        $this->assertEquals(150, $registerConfig['limits']['global']['max_attempts']);

        $this->assertTrue($registerConfig['limits']['email']['enabled']);
        $this->assertEquals(3, $registerConfig['limits']['email']['max_attempts']);

        $this->assertTrue($registerConfig['limits']['ip']['enabled']);
        $this->assertEquals(3, $registerConfig['limits']['ip']['max_attempts']);
    }

    #[Test]
    public function login_limiter_configuration(): void
    {
        $loginConfig = Config::get('rate-limiting.limiters.login');

        $this->assertTrue($loginConfig['enabled']);
        $this->assertEquals('linear', $loginConfig['growth_strategy']);

        // Test limits
        $this->assertFalse($loginConfig['limits']['global']['enabled']); // Disabled by default
        $this->assertEquals(1000, $loginConfig['limits']['global']['max_attempts']);

        $this->assertTrue($loginConfig['limits']['username_ip']['enabled']);
        $this->assertEquals(5, $loginConfig['limits']['username_ip']['max_attempts']);

        $this->assertTrue($loginConfig['limits']['ip']['enabled']);
        $this->assertEquals(10, $loginConfig['limits']['ip']['max_attempts']);
    }

    #[Test]
    public function forgot_password_limiter_configuration(): void
    {
        $forgotConfig = Config::get('rate-limiting.limiters.forgot-password');

        $this->assertTrue($forgotConfig['enabled']);
        $this->assertEquals('linear', $forgotConfig['growth_strategy']);

        // Test limits
        $this->assertFalse($forgotConfig['limits']['global']['enabled']); // Disabled by default
        $this->assertEquals(500, $forgotConfig['limits']['global']['max_attempts']);

        $this->assertTrue($forgotConfig['limits']['email']['enabled']);
        $this->assertEquals(3, $forgotConfig['limits']['email']['max_attempts']);

        $this->assertTrue($forgotConfig['limits']['ip']['enabled']);
        $this->assertEquals(5, $forgotConfig['limits']['ip']['max_attempts']);
    }

    #[Test]
    public function two_factor_limiter_configuration(): void
    {
        $twoFactorConfig = Config::get('rate-limiting.limiters.two-factor');

        $this->assertTrue($twoFactorConfig['enabled']);
        $this->assertEquals('fibonacci', $twoFactorConfig['growth_strategy']);

        // Test limits
        $this->assertFalse($twoFactorConfig['limits']['global']['enabled']); // Disabled by default
        $this->assertEquals(1000, $twoFactorConfig['limits']['global']['max_attempts']);

        $this->assertTrue($twoFactorConfig['limits']['session']['enabled']);
        $this->assertEquals(5, $twoFactorConfig['limits']['session']['max_attempts']);

        $this->assertTrue($twoFactorConfig['limits']['ip']['enabled']);
        $this->assertEquals(10, $twoFactorConfig['limits']['ip']['max_attempts']);
    }

    #[Test]
    public function rate_limit_messages_configuration(): void
    {
        // Test register messages
        $registerMessages = Config::get('rate-limiting.messages.register');
        $this->assertStringContainsString('registration attempts across the system', $registerMessages['global']);
        $this->assertStringContainsString('registration attempts with this email', $registerMessages['email']);
        $this->assertStringContainsString('registration attempts from your location', $registerMessages['ip']);

        // Test login messages
        $loginMessages = Config::get('rate-limiting.messages.login');
        $this->assertStringContainsString('login attempts across the system', $loginMessages['global']);
        $this->assertStringContainsString('login attempts with this username', $loginMessages['username_ip']);
        $this->assertStringContainsString('login attempts from your location', $loginMessages['ip']);

        // Test forgot-password messages
        $forgotMessages = Config::get('rate-limiting.messages.forgot-password');
        $this->assertStringContainsString('password reset attempts across the system', $forgotMessages['global']);
        $this->assertStringContainsString('password reset attempts for this email', $forgotMessages['email']);
        $this->assertStringContainsString('password reset attempts from your location', $forgotMessages['ip']);

        // Test two-factor messages
        $twoFactorMessages = Config::get('rate-limiting.messages.two-factor');
        $this->assertStringContainsString(
            'two-factor authentication attempts across the system',
            $twoFactorMessages['global'],
        );
        $this->assertStringContainsString(
            'two-factor authentication attempts for this session',
            $twoFactorMessages['session'],
        );
        $this->assertStringContainsString(
            'two-factor authentication attempts from your location',
            $twoFactorMessages['ip'],
        );

        // Test default message
        $defaultMessage = Config::get('rate-limiting.messages.default');
        $this->assertStringContainsString('Too many attempts', $defaultMessage);
        $this->assertStringContainsString(':minutes', $defaultMessage);
    }

    #[Test]
    public function warning_messages_configuration(): void
    {
        $warningMessages = Config::get('rate-limiting.warning_messages');

        // Test base warning message
        $this->assertStringContainsString(':attempts attempt(s) remaining', $warningMessages['base']);

        // Test suggestions
        $suggestions = $warningMessages['suggestions'];
        $this->assertStringContainsString('forgotten your password', $suggestions['login']);
        $this->assertStringContainsString('double-check your information', $suggestions['register']);
        $this->assertStringContainsString('verify the email address', $suggestions['forgot-password']);
        $this->assertStringContainsString('Double-check your authenticator', $suggestions['two-factor']);
        $this->assertStringContainsString('verify your information', $suggestions['default']);
    }

    #[Test]
    public function suggestion_messages_configuration(): void
    {
        $suggestions = Config::get('rate-limiting.suggestions');

        // Test login suggestions
        $this->assertStringContainsString('resetting your password', $suggestions['login']['high_attempts']);
        $this->assertStringContainsString(
            'double-check your email and password',
            $suggestions['login']['low_attempts'],
        );

        // Test other suggestions
        $this->assertStringContainsString('verify all required fields', $suggestions['register']);
        $this->assertStringContainsString('ensure the email address is correct', $suggestions['forgot-password']);

        // Test two-factor suggestions
        $this->assertStringContainsString('recovery code instead', $suggestions['two-factor']['high_attempts']);
        $this->assertStringContainsString('check your authenticator app', $suggestions['two-factor']['low_attempts']);

        // Test default suggestion
        $this->assertStringContainsString('wait before trying again', $suggestions['default']);
    }

    #[Test]
    public function environment_variable_overrides(): void
    {
        // Test that environment variables can override config values
        Config::set('rate-limiting.enabled', false);
        $this->assertFalse(Config::get('rate-limiting.enabled'));

        Config::set('rate-limiting.max_suspension_time', 7200);
        $this->assertEquals(7200, Config::get('rate-limiting.max_suspension_time'));

        Config::set('rate-limiting.username_field', 'username');
        $this->assertEquals('username', Config::get('rate-limiting.username_field'));
    }

    #[Test]
    public function limiter_specific_environment_overrides(): void
    {
        // Test register limiter overrides
        Config::set('rate-limiting.limiters.register.enabled', false);
        $this->assertFalse(Config::get('rate-limiting.limiters.register.enabled'));

        Config::set('rate-limiting.limiters.register.growth_strategy', 'exponential');
        $this->assertEquals('exponential', Config::get('rate-limiting.limiters.register.growth_strategy'));

        Config::set('rate-limiting.limiters.register.limits.email.max_attempts', 5);
        $this->assertEquals(5, Config::get('rate-limiting.limiters.register.limits.email.max_attempts'));
    }

    #[Test]
    public function message_environment_overrides(): void
    {
        // Test custom message override
        Config::set('rate-limiting.messages.register.email', 'Custom registration message');
        $this->assertEquals('Custom registration message', Config::get('rate-limiting.messages.register.email'));

        Config::set('rate-limiting.messages.default', 'Custom default message');
        $this->assertEquals('Custom default message', Config::get('rate-limiting.messages.default'));
    }

    #[Test]
    public function configuration_structure_integrity(): void
    {
        // Ensure all required configuration keys exist
        $config = Config::get('rate-limiting');

        // Global settings
        $this->assertArrayHasKey('enabled', $config);
        $this->assertArrayHasKey('log_violations', $config);
        $this->assertArrayHasKey('max_suspension_time', $config);
        $this->assertArrayHasKey('username_field', $config);
        $this->assertArrayHasKey('username_resolver', $config);

        // Limiters
        $this->assertArrayHasKey('limiters', $config);
        $limiters = ['register', 'login', 'forgot-password', 'two-factor'];
        foreach ($limiters as $limiter) {
            $this->assertArrayHasKey($limiter, $config['limiters']);
            $this->assertArrayHasKey('enabled', $config['limiters'][$limiter]);
            $this->assertArrayHasKey('growth_strategy', $config['limiters'][$limiter]);
            $this->assertArrayHasKey('limits', $config['limiters'][$limiter]);
        }

        // Messages
        $this->assertArrayHasKey('messages', $config);
        $this->assertArrayHasKey('warning_messages', $config);
        $this->assertArrayHasKey('suggestions', $config);
    }

    #[Test]
    public function growth_strategy_values(): void
    {
        $validStrategies = ['linear', 'exponential', 'fibonacci'];

        foreach (Config::get('rate-limiting.limiters') as $limiterName => $limiterConfig) {
            $strategy = $limiterConfig['growth_strategy'];
            $this->assertContains(
                $strategy,
                $validStrategies,
                "Invalid growth strategy '{$strategy}' for limiter '{$limiterName}'",
            );
        }
    }

    #[Test]
    public function max_attempts_are_positive_integers(): void
    {
        foreach (Config::get('rate-limiting.limiters') as $limiterName => $limiterConfig) {
            foreach ($limiterConfig['limits'] as $limitType => $limitConfig) {
                $maxAttempts = $limitConfig['max_attempts'];
                $this->assertIsInt($maxAttempts, "max_attempts for {$limiterName}.{$limitType} should be integer");
                $this->assertGreaterThan(
                    0,
                    $maxAttempts,
                    "max_attempts for {$limiterName}.{$limitType} should be positive",
                );
            }
        }
    }

    #[Test]
    public function boolean_configuration_values(): void
    {
        // Test boolean values are actually booleans
        $this->assertIsBool(Config::get('rate-limiting.enabled'));
        $this->assertIsBool(Config::get('rate-limiting.log_violations'));

        foreach (Config::get('rate-limiting.limiters') as $limiterName => $limiterConfig) {
            $this->assertIsBool($limiterConfig['enabled'], "enabled for {$limiterName} should be boolean");

            foreach ($limiterConfig['limits'] as $limitType => $limitConfig) {
                $this->assertIsBool(
                    $limitConfig['enabled'],
                    "enabled for {$limiterName}.{$limitType} should be boolean",
                );
            }
        }
    }
}
