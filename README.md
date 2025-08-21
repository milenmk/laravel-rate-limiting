# Laravel Advanced Rate Limiting Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/milenmk/laravel-rate-limiting.svg?style=flat-square)](https://packagist.org/packages/milenmk/laravel-rate-limiting)
[![Total Downloads](https://img.shields.io/packagist/dt/milenmk/laravel-rate-limiting.svg?style=flat-square)](https://packagist.org/packages/milenmk/laravel-rate-limiting)

An advanced rate limiting package for Laravel with exponential backoff, custom messages, and built-in Blade components.
This package provides comprehensive protection against DDoS attacks, brute force attempts, and other malicious
activities while maintaining a positive user experience for legitimate users.

## Features

- **Multi-layered protection** with configurable limits for different authentication endpoints
- **Intelligent backoff strategies** (linear, Fibonacci, and exponential)
- **Granular limit types** (global, per-email, per-IP, per-session, per-username+IP)
- **Progressive user feedback** with proactive warnings before lockouts
- **Context-aware messaging** with intelligent suggestions based on the situation
- **Built-in Blade components** for error and warning messages
- **Comprehensive logging** and monitoring capabilities
- **Flexible configuration** with easy enable/disable controls

## Architecture

The system is built around a dedicated service provider that implements a configuration-driven approach:

- **Separation of concerns**: Rate limiting logic is isolated from application logic
- **Configuration-driven design**: All limiters are defined in a single, maintainable configuration array
- **Extensible architecture**: New limiters and limit types can be added with minimal code changes
- **Performance optimized**: Uses Laravel's cache system with efficient key generation

## Requirements

- PHP 8.2 or higher
- Laravel 10.x, 11.x, or 12.x
- Laravel Fortify (for authentication endpoints)

## Installation

You can install the package via Composer:

```bash
composer require milenmk/laravel-rate-limiting
```

### Publish Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=rate-limiting-config
```

This will create a `config/rate-limiting.php` file where you can customize all rate limiting settings.

### Publish Environment Variables Template

Publish the environment variables template:

```bash
php artisan vendor:publish --tag=rate-limiting-env
```

This will create a `.env.rate-limiting.example` file with all available environment variables that you can copy to your
`.env` file.

### Publish Views (Optional)

If you want to customize the Blade components, you can publish the views:

```bash
php artisan vendor:publish --tag=rate-limiting-views
```

This will publish the views to `resources/views/vendor/milenmk/laravel-rate-limiting/`.

## Supported Endpoints

The package protects the following authentication endpoints:

| Endpoint                      | Purpose                 | Default Limits                     |
| ----------------------------- | ----------------------- | ---------------------------------- |
| **Registration**              | User account creation   | Per-email, per-IP, optional global |
| **Login**                     | User authentication     | Per-username+IP, per-IP            |
| **Forgot Password**           | Password reset requests | Per-email, per-IP                  |
| **Two-Factor Authentication** | 2FA verification        | Per-session, per-IP                |

## Growth Strategies

The system supports three distinct backoff strategies to balance security with user experience.

**Key Concept**: Rate limiting delays are only applied to attempts that EXCEED the configured maximum. For example, if
the limit is set to 5 attempts, the first 5 failed attempts will show normal validation errors. Rate limiting delays
begin with the 6th attempt (1st excess attempt) and increase according to the chosen strategy.

### Strategy Comparison

**Important**: Rate limiting only applies to attempts that EXCEED the configured maximum. The table below shows delay
times for attempts beyond the limit. The delay time of 60 min specified in the table is the maximum suspension time set
in the configuration file (default to 60 min).

| Excess Attempt | Linear | Fibonacci | Exponential (2^n) |
| -------------- | ------ | --------- | ----------------- |
| 1st excess     | 1 min  | 1 min     | 1 min             |
| 2nd excess     | 2 min  | 2 min     | 2 min             |
| 3rd excess     | 3 min  | 3 min     | 4 min             |
| 4th excess     | 4 min  | 5 min     | 8 min             |
| 5th excess     | 5 min  | 8 min     | 16 min            |
| 6th excess     | 6 min  | 13 min    | 32 min            |
| 7th excess     | 7 min  | 21 min    | 60 min            |
| 8th excess     | 8 min  | 34 min    | 60 min            |
| 9th excess     | 9 min  | 55 min    | 60 min            |
| 10th excess    | 10 min | 60 min    | 60 min            |

**Example**: Consider a user making repeated failed login attempts with Fibonacci strategy and a limit of 5 attempts:

- **Attempts 1-5**: Normal failed attempts, no rate limiting applied
- **6th attempt** (1st excess): User is blocked for 1 minute
- **7th attempt** (2nd excess): After waiting 1 minute, if they fail again, blocked for 2 minutes
- **8th attempt** (3rd excess): After waiting 2 minutes, if they fail again, blocked for 3 minutes
- **9th attempt** (4th excess): After waiting 3 minutes, if they fail again, blocked for 5 minutes
- **10th attempt** (5th excess): After waiting 5 minutes, if they fail again, blocked for 8 minutes

This gradual increase in waiting time effectively deters automated attacks while remaining manageable for legitimate
users who occasionally make mistakes.

### Strategy Selection Guide

- **Linear**: Predictable, gentle escalation. Best for development/testing environments or when user experience is
  prioritized over security.

- **Fibonacci** ⭐ **Recommended**: Provides an optimal balance between user experience and security. Grows more
  aggressively than linear but remains reasonable for legitimate users who make mistakes.

- **Exponential**: Maximum security with aggressive escalation. Use only for highly sensitive endpoints or when under
  active attack.

## Limit Types

Each endpoint supports multiple limit types that can be independently configured:

### Registration Limits

- **Global**: System-wide registration attempts (recommended: enabled with high limits ~150+)
- **Email**: Per-email address attempts (prevents account enumeration)
- **IP**: Per-IP address attempts (prevents distributed attacks from single source)

### Login Limits

- **Global**: System-wide login attempts (recommended: disabled to avoid impacting legitimate users)
- **Username+IP**: Per-username and IP combination (prevents targeted attacks)
- **IP**: Per-IP address attempts (prevents brute force from single source)

### Forgot Password Limits

- **Global**: System-wide password reset attempts (recommended: disabled)
- **Email**: Per-email address attempts (prevents email flooding)
- **IP**: Per-IP address attempts (prevents abuse)

### Two-Factor Authentication Limits

- **Global**: System-wide 2FA attempts (recommended: disabled)
- **Session**: Per-session attempts (most effective for 2FA)
- **IP**: Per-IP address attempts (additional protection)

## Configuration

### Environment Variables

The package includes a comprehensive `.env.rate-limiting.example` file with all available configuration options. Copy
the relevant variables to your `.env` file and adjust as needed:

```env
# Global Rate Limiting Settings
RATE_LIMITING_ENABLED=true
RATE_LIMITING_LOG_VIOLATIONS=true
RATE_LIMITING_MAX_SUSPENSION_TIME=3600

# Registration Rate Limiting
RATE_LIMITING_REGISTER_ENABLED=true
RATE_LIMITING_REGISTER_GROWTH=linear
RATE_LIMITING_REGISTER_EMAIL_MAX_ATTEMPTS=3
RATE_LIMITING_REGISTER_IP_MAX_ATTEMPTS=3

# Login Rate Limiting
RATE_LIMITING_LOGIN_ENABLED=true
RATE_LIMITING_LOGIN_GROWTH=linear
RATE_LIMITING_LOGIN_USERNAME_IP_MAX_ATTEMPTS=5
RATE_LIMITING_LOGIN_IP_MAX_ATTEMPTS=10

# And many more...
```

### Configuration Examples

#### Basic Configuration

Suitable for most applications with standard security requirements:

```env
# Enable rate limiting globally
RATE_LIMITING_ENABLED=true

# Registration protection
RATE_LIMITING_REGISTER_ENABLED=true
RATE_LIMITING_REGISTER_GROWTH=linear
RATE_LIMITING_REGISTER_EMAIL_MAX_ATTEMPTS=3
RATE_LIMITING_REGISTER_IP_MAX_ATTEMPTS=3

# Login protection
RATE_LIMITING_LOGIN_ENABLED=true
RATE_LIMITING_LOGIN_USERNAME_IP_MAX_ATTEMPTS=5
```

#### Balanced Security Configuration (Recommended)

Provides optimal security-to-usability ratio for production environments:

```env
# Fibonacci backoff for balanced approach
RATE_LIMITING_REGISTER_GROWTH=fibonacci
RATE_LIMITING_LOGIN_GROWTH=fibonacci
RATE_LIMITING_FORGOT_PASSWORD_GROWTH=fibonacci
RATE_LIMITING_TWO_FACTOR_GROWTH=fibonacci

# Moderate attempt limits
RATE_LIMITING_REGISTER_EMAIL_MAX_ATTEMPTS=2
RATE_LIMITING_LOGIN_USERNAME_IP_MAX_ATTEMPTS=3
RATE_LIMITING_TWO_FACTOR_SESSION_MAX_ATTEMPTS=3
```

#### Maximum Security Configuration

For high-risk environments or during active attacks:

```env
# Exponential backoff for maximum protection
RATE_LIMITING_REGISTER_GROWTH=exponential
RATE_LIMITING_LOGIN_GROWTH=exponential
RATE_LIMITING_FORGOT_PASSWORD_GROWTH=exponential
RATE_LIMITING_TWO_FACTOR_GROWTH=exponential

# Very low attempt limits
RATE_LIMITING_REGISTER_EMAIL_MAX_ATTEMPTS=2
RATE_LIMITING_LOGIN_USERNAME_IP_MAX_ATTEMPTS=2
RATE_LIMITING_TWO_FACTOR_SESSION_MAX_ATTEMPTS=2
```

#### Development Configuration

For development and testing environments:

```env
# Disable rate limiting for development
RATE_LIMITING_ENABLED=false

# Or use high limits for testing
RATE_LIMITING_REGISTER_EMAIL_MAX_ATTEMPTS=100
RATE_LIMITING_LOGIN_USERNAME_IP_MAX_ATTEMPTS=100
```

## Global Limiters

Global limiters provide system-wide protection but require careful consideration:

### Benefits

- **System-wide protection**: Prevents overwhelming the entire system
- **Resource protection**: Protects database and cache resources
- **DDoS mitigation**: Effective against distributed attacks

### Considerations

- **Legitimate user impact**: May block legitimate users during high-traffic periods
- **False positives**: Normal usage spikes may trigger limits
- **Shared hosting**: Multiple applications may compete for the same limits

### Recommendations

- **Registration**: Enable with high limits (150+ attempts) to prevent spam
- **Login**: Generally disable to avoid impacting legitimate users
- **Forgot Password**: Usually unnecessary; per-email limits are sufficient
- **Two-Factor**: Session-based limits are more appropriate

## Usage

### Built-in Blade Components

The package includes ready-to-use Blade components for displaying rate limit messages. **No need to create these
components yourself** - they are automatically available after package installation.

#### Error Messages

Display rate limit errors when limits are exceeded:

```blade
{{-- Login Page Example --}}
<x-error-message field="rate_limit" :title="__('Login Temporarily Blocked')" class="my-4 p-4" />

{{-- Registration Page Example --}}
<x-error-message field="rate_limit" :title="__('Registration Temporarily Blocked')" class="my-4 p-4" />

{{-- Forgot Password Page Example --}}
<x-error-message field="rate_limit" :title="__('Password Reset Temporarily Blocked')" class="my-4 p-4" />

{{-- Two-Factor Authentication Page Example --}}
<x-error-message field="rate_limit" :title="__('Two-Factor Authentication Temporarily Blocked')" class="my-4 p-4" />
```

#### Warning Messages

Display proactive warnings when users are approaching limits:

```blade
{{-- Show warning when approaching rate limit --}}
<x-warning-message :title="__('Login Warning')" class="my-4 p-4" />

{{-- With custom message --}}
<x-warning-message message="Custom warning message" :title="__('Warning')" class="my-4 p-4" />
```

The warning component automatically displays session-based warnings when users are approaching their rate limits.

#### Complete Integration Example

Here's how to integrate both error and warning messages in an authentication form:

```blade
{{-- resources/views/auth/login.blade.php --}}
<form method="POST" action="{{ route('login') }}">
    @csrf

    {{-- Rate Limit Error Display --}}
    <x-error-message field="rate_limit" :title="__('Login Temporarily Blocked')" class="my-4 p-4" />

    {{-- Rate Limit Warning Display --}}
    <x-warning-message :title="__('Login Warning')" class="my-4 p-4" />

    {{-- Email Field --}}
    <div class="mb-4">
        <label for="email">{{ __('Email') }}</label>
        <input type="email" name="email" id="email" required />
        <x-error-message field="email" />
    </div>

    {{-- Password Field --}}
    <div class="mb-4">
        <label for="password">{{ __('Password') }}</label>
        <input type="password" name="password" id="password" required />
        <x-error-message field="password" />
    </div>

    {{-- Submit Button --}}
    <button type="submit">{{ __('Login') }}</button>

    {{-- Password Reset Link --}}
    <a href="{{ route('password.request') }}">{{ __('Forgot Password?') }}</a>
</form>
```

### Component Properties

Both components accept the following properties:

- `field` (error-message only): The error field name (default: '')
- `message` (warning-message only): Custom message to display (default: '')
- `title`: The title/heading for the message (default: null)
- `class`: CSS classes to apply (default: 'my-2 p-2')

### Automatic Integration

The package automatically integrates with Laravel Fortify's authentication endpoints. No additional setup is required -
rate limiting will be applied to:

- Registration attempts
- Login attempts
- Password reset requests
- Two-factor authentication attempts

## Message Customization

The system provides intelligent, context-aware messages that adapt based on the situation:

### Error Message Examples

- **Login**: "Too many login attempts with this username from your location. Please wait 5 minutes before trying again.
  Consider resetting your password if you've forgotten it, or contact support if you believe this is an error."

- **Registration**: "Too many registration attempts from your location. Please wait 3 minutes before trying again.
  Please verify all required fields are filled correctly and try again later."

- **Two-Factor**: "Too many two-factor authentication attempts for this session. Please wait 8 minutes before trying
  again. If you're having trouble with your authenticator, try using a recovery code instead."

### Warning Message Examples

- **Login Warning**: "You have 2 attempt(s) remaining before a temporary lockout. If you've forgotten your password,
  consider using the 'Reset Password' link below."

- **Registration Warning**: "You have 1 attempt(s) remaining before a temporary lockout. Please double-check your
  information before submitting."

- **Two-Factor Warning**: "You have 2 attempt(s) remaining before a temporary lockout. Double-check your authenticator
  app or recovery code."

### Custom Messages

The system supports three types of customizable messages:

#### 1. Error Messages

Error messages are shown when rate limits are exceeded:

```env
# Basic error messages (shown when limits are exceeded)
RATE_LIMITING_REGISTER_EMAIL_MESSAGE="Too many registration attempts with this email. Please wait :minutes minutes."
RATE_LIMITING_LOGIN_USERNAME_IP_MESSAGE="Too many login attempts. Please wait :minutes minutes before trying again."
RATE_LIMITING_FORGOT_PASSWORD_EMAIL_MESSAGE="Too many password reset attempts. Please wait :minutes minutes."
RATE_LIMITING_TWO_FACTOR_SESSION_MESSAGE="Too many 2FA attempts. Please wait :minutes minutes."
```

#### 2. Warning Messages

Warning messages are shown when users are approaching rate limits:

```env
# Base warning message template
RATE_LIMITING_WARNING_BASE_MESSAGE="You have :attempts attempt(s) remaining before a temporary lockout."

# Context-specific warning suggestions
RATE_LIMITING_WARNING_LOGIN_SUGGESTION="If you've forgotten your password, consider using the 'Reset Password' link below."
RATE_LIMITING_WARNING_REGISTER_SUGGESTION="Please double-check your information before submitting."
RATE_LIMITING_WARNING_FORGOT_PASSWORD_SUGGESTION="Please verify the email address is correct."
RATE_LIMITING_WARNING_TWO_FACTOR_SUGGESTION="Double-check your authenticator app or recovery code."
```

#### 3. Suggestion Messages

Suggestion messages are appended to error messages to provide helpful guidance:

```env
# Login suggestions (based on attempt count)
RATE_LIMITING_LOGIN_HIGH_ATTEMPTS_SUGGESTION="Consider resetting your password if you've forgotten it, or contact support if you believe this is an error."
RATE_LIMITING_LOGIN_LOW_ATTEMPTS_SUGGESTION="Please double-check your email and password."

# Two-factor suggestions (based on attempt count)
RATE_LIMITING_TWO_FACTOR_HIGH_ATTEMPTS_SUGGESTION="If you're having trouble with your authenticator, try using a recovery code instead."
RATE_LIMITING_TWO_FACTOR_LOW_ATTEMPTS_SUGGESTION="Please check your authenticator app for the current code."

# Simple suggestions (same regardless of attempt count)
RATE_LIMITING_REGISTER_SUGGESTION="Please verify all required fields are filled correctly and try again later."
RATE_LIMITING_FORGOT_PASSWORD_SUGGESTION="Please ensure the email address is correct and check your spam folder for previous reset emails."
```

#### Message Placeholders

- **`:minutes`** - Automatically replaced with wait time in minutes (error messages)
- **`:attempts`** - Automatically replaced with remaining attempts count (warning messages)

#### Smart Suggestion Logic

The system automatically selects appropriate suggestions based on context:

- **Login & Two-Factor**: Uses different suggestions for high attempts (≥3) vs low attempts (<3)
- **Registration & Forgot Password**: Uses consistent suggestions regardless of attempt count
- **Fallback**: Provides default suggestions if specific ones aren't configured

## User Experience Flow

### Progressive User Feedback

The system provides a progressive feedback experience that guides users through rate limiting scenarios:

#### Normal Operation

1. **Successful attempts**: No rate limiting messages shown
2. **Failed attempts**: Standard validation errors displayed

#### Approaching Limits (2 or fewer attempts remaining)

1. **Warning displayed**: "You have 2 attempt(s) remaining before a temporary lockout."
2. **Context-specific guidance**: Appropriate suggestions based on the endpoint
3. **Proactive help**: Encourages correct action before lockout

#### Rate Limit Exceeded

1. **Clear error message**: Explains what happened and how long to wait
2. **Helpful suggestions**: Context-aware guidance for resolution
3. **Recovery options**: Suggests alternative actions (password reset, support contact)

#### Example User Journey (Login with 5 attempt limit)

```
Attempts 1-3: Normal login attempts with standard validation errors
    ↓
Attempt 4: Warning appears: "You have 2 attempts remaining before a temporary lockout.
           If you've forgotten your password, consider using the 'Reset Password' link below."
    ↓
Attempt 5: Warning appears: "You have 1 attempt remaining before a temporary lockout.
           If you've forgotten your password, consider using the 'Reset Password' link below."
    ↓
Attempt 6: Error appears: "Too many login attempts with this username from your location.
           Please wait 1 minute before trying again. Consider resetting your password
           if you've forgotten it, or contact support if you believe this is an error."
    ↓
Attempt 7: (after 1 minute wait) Error appears: "Please wait 2 minutes before trying again..."
    ↓
Attempt 8: (after 2 minute wait) Error appears: "Please wait 3 minutes before trying again..."
```

### Benefits of Progressive Feedback

- **Prevents frustration**: Users get warnings before being locked out
- **Reduces support burden**: Clear guidance helps users self-resolve issues
- **Maintains security**: All security protections remain fully effective
- **Improves conversion**: Users are less likely to abandon the process

## Monitoring and Logging

When `RATE_LIMITING_LOG_VIOLATIONS=true`, the package logs detailed information about violations:

```json
{
    "message": "Rate limit exceeded for register:email: register:email:user@example.com",
    "context": {
        "wait_seconds": 180,
        "attempts": 4,
        "ip": "192.168.1.100",
        "user_agent": "Mozilla/5.0...",
        "limiter_type": "register",
        "limit_type": "email",
        "growth_strategy": "fibonacci"
    }
}
```

### Monitoring Best Practices

1. **Regular log review**: Monitor for unusual patterns or attack attempts
2. **Alert configuration**: Set up alerts for high violation rates
3. **Performance monitoring**: Track cache usage and response times
4. **User feedback**: Monitor support requests related to access issues

## Performance Considerations

- **Cache efficiency**: Uses Laravel's cache system with optimized key structures
- **Minimal overhead**: Processing only occurs when limits are approached
- **Memory management**: Configurable maximum suspension times prevent indefinite cache growth
- **Scalability**: Designed to handle high-traffic applications

## Security Best Practices

1. **Regular monitoring**: Review logs for attack patterns and adjust limits accordingly
2. **Limit tuning**: Fine-tune based on legitimate user behavior patterns
3. **HTTPS enforcement**: Always use HTTPS to prevent session hijacking
4. **IP whitelisting**: Consider whitelisting trusted IP ranges for administrative access
5. **Configuration review**: Periodically review and update rate limiting configurations
6. **Incident response**: Have procedures for quickly adjusting limits during attacks

## Customization

### Custom Blade Components

If you need to customize the appearance of the error and warning components, publish the views and modify them:

```bash
php artisan vendor:publish --tag=rate-limiting-views
```

Then edit the files in `resources/views/vendor/rate-limiting/components/`.

### Adding New Limiters

The system is designed for easy extension. To add a new limiter:

1. **Add to configuration array** in `RateLimitingServiceProvider`:

```php
'my-custom-endpoint' => [
    'global' => fn() => 'global',
    'user' => fn(Request $request) => 'user:' . ($request->user()?->id ?: 'guest'),
    'ip' => fn(Request $request) => 'ip:' . $request->ip(),
],
```

2. **Add configuration** in `config/rate-limiting.php`:

```php
'my-custom-endpoint' => [
    'enabled' => env('RATE_LIMITING_MY_CUSTOM_ENABLED', true),
    'growth_strategy' => env('RATE_LIMITING_MY_CUSTOM_GROWTH', 'fibonacci'),
    'limits' => [
        'global' => [
            'enabled' => env('RATE_LIMITING_MY_CUSTOM_GLOBAL_ENABLED', false),
            'max_attempts' => env('RATE_LIMITING_MY_CUSTOM_GLOBAL_MAX_ATTEMPTS', 1000),
        ],
        // ... additional limit types
    ],
],
```

## Troubleshooting

### Common Issues

**Legitimate users being blocked**

- **Solution**: Increase attempt limits or switch from exponential to Fibonacci/linear growth
- **Prevention**: Monitor user behavior patterns and adjust limits accordingly

**Rate limiting not working**

- **Check**: Ensure `RATE_LIMITING_ENABLED=true` in your `.env` file
- **Check**: Verify the specific limiter is enabled (e.g., `RATE_LIMITING_LOGIN_ENABLED=true`)
- **Check**: Confirm cache is working properly

**Messages not displaying**

- **Check**: Ensure you're using the correct component names (`<x-error-message>` and `<x-warning-message>`)
- **Check**: Verify the `field` parameter matches the error key (`rate_limit`)

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Milen Karaganski](https://github.com/milenmk)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
