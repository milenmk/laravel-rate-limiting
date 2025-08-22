# Changelog

## v1.0.3

#### Published at: 2025-08-22

- [FIX] Rate limiter reset after reaching max attempts, instead of blocking the request

## v1.0.2

#### Published at: 2025-08-21

- [FIX] Wrong path to copy the blade components
- [FIX] Error message not shown when limit is hit

## v1.0.1

#### Published at: 2025-08-21

- Refactors RateLimitingServiceProvider view loading, and Blade component handling.

## v1.0.0

#### Published at: 2025-08-21

### Added

- Initial release of Laravel Advanced Rate Limiting package
- Multi-layered protection for authentication endpoints (registration, login, forgot password, two-factor)
- Three growth strategies: linear, Fibonacci, and exponential backoff
- Granular limit types: global, per-email, per-IP, per-session, per-username+IP
- Built-in Blade components for error and warning messages
- Progressive user feedback with proactive warnings before lockouts
- Context-aware messaging with intelligent suggestions
- Comprehensive logging and monitoring capabilities
- Flexible configuration with environment variable support
- Support for Laravel 10.x, 11.x, and 12.x
- Complete documentation and examples
- Environment variables template (.env.rate-limiting.example)
- **Hybrid Username Resolution**: Intelligent username field detection with multiple fallback strategies

### Features

- **RateLimitingServiceProvider**: Core service provider with automatic Laravel integration
- **Error Message Component**: `<x-error-message>` for displaying rate limit violations
- **Warning Message Component**: `<x-warning-message>` for proactive user warnings
- **Configurable Growth Strategies**: Choose between linear, Fibonacci, or exponential backoff
- **Intelligent Key Generation**: Optimized cache key generation for different limit types
- **Omnipotent Integration**: Works with or without Laravel Fortify - automatically detects and adapts
- **Flexible Username Resolution**: Custom resolvers, Fortify integration, configurable fields, and smart fallbacks
- **Customizable Messages**: Full control over error messages and suggestions
- **Performance Optimized**: Efficient cache usage with minimal overhead

### Configuration

- Complete configuration file with sensible defaults
- Environment variable support for all settings
- Per-endpoint enable/disable controls
- Customizable maximum suspension times
- Flexible message templates with localization support
