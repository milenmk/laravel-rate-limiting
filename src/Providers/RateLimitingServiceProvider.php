<?php

declare(strict_types=1);

namespace Milenmk\LaravelRateLimiting\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Cache\RateLimiting\Unlimited;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class RateLimitingServiceProvider extends ServiceProvider
{
    /**
     * Rate limiter configurations with their key generators
     */
    private array $limiterConfigurations;

    public function register(): void
    {
        parent::register();

        // Merge package config with application config
        $this->mergeConfigFrom(__DIR__ . '/../../config/rate-limiting.php', 'rate-limiting');

        // Initialize the property dynamically here
        $this->limiterConfigurations = [
            'register' => [
                'global' => fn () => 'global',
                'email' => fn (Request $request) => 'email:' . ($request->input('email') ?: 'unknown'),
                'ip' => fn (Request $request) => 'ip:' . $request->ip(),
            ],
            'login' => [
                'global' => fn () => 'global',
                'username_ip' => fn (Request $request) => 'username_ip:' .
                    Str::transliterate(Str::lower($this->resolveUsername($request))) .
                    '|' .
                    $request->ip(),
                'ip' => fn (Request $request) => 'ip:' . $request->ip(),
            ],
            'forgot-password' => [
                'global' => fn () => 'global',
                'email' => fn (Request $request) => 'email:' . ($request->input('email') ?: 'unknown'),
                'ip' => fn (Request $request) => 'ip:' . $request->ip(),
            ],
            'two-factor' => [
                'global' => fn () => 'global',
                'session' => fn (Request $request) => 'session:' . ($request->session()->get('login.id') ?: 'unknown'),
                'ip' => fn (Request $request) => 'ip:' . $request->ip(),
            ],
        ];
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish config file
        $this->publishes(
            [
                __DIR__ . '/../../config/rate-limiting.php' => config_path('rate-limiting.php'),
            ],
            'rate-limiting-config',
        );

        // Publish .env example file
        $this->publishes(
            [
                __DIR__ . '/../../.env.rate-limiting.example' => base_path('.env.rate-limiting.example'),
            ],
            'rate-limiting-env',
        );

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'rate-limiting');

        // Publish views (optional)
        $this->publishes(
            [
                __DIR__ . '/../../resources/views' => resource_path('views/vendor/milenmk/laravel-rate-limiting'),
            ],
            'rate-limiting-views',
        );

        // Register Blade components
        Blade::anonymousComponentNamespace('rate-limiting::components', '');

        if (! Config::get('rate-limiting.enabled', true)) {
            // Clear any existing limiters when disabled
            if (isset($this->limiterConfigurations)) {
                foreach (array_keys($this->limiterConfigurations) as $limiterName) {
                    RateLimiter::for($limiterName, fn () => null);
                }
            }

            return;
        }

        foreach ($this->limiterConfigurations as $limiterName => $limitTypes) {
            $this->configureRateLimiter($limiterName, $limitTypes);
        }
    }

    /**
     * Resolve the username field from the request with intelligent fallback
     *
     * This method provides a hybrid approach for username resolution:
     * 1. Check for custom resolver callback (advanced users)
     * 2. Use Fortify's username field if Fortify is installed
     * 3. Fallback to configurable field name (most users)
     * 4. Try common field names as last resort
     */
    private function resolveUsername(Request $request): string
    {
        // Check for custom resolver first (advanced users)
        $resolver = Config::get('rate-limiting.username_resolver');
        if ($resolver && is_callable($resolver)) {
            $result = $resolver($request);
            if ($result !== null) {
                return (string) $result;
            }
        }

        // Use Fortify's username field if Fortify is available
        $fortifyClass = 'Laravel\Fortify\Fortify';
        if (class_exists($fortifyClass)) {
            $field = $fortifyClass::username();
            $value = $request->input($field);
            if ($value !== null) {
                return (string) $value;
            }
        }

        // Fallback to configurable field name
        $configField = Config::get('rate-limiting.username_field', 'email');
        $value = $request->input($configField);
        if ($value !== null) {
            return (string) $value;
        }

        // Try common field names as last resort
        $commonFields = ['email', 'username', 'login', 'user_email', 'user_name'];
        foreach ($commonFields as $field) {
            $value = $request->input($field);
            if ($value !== null) {
                return (string) $value;
            }
        }

        return 'unknown';
    }

    /**
     * Configure a rate limiter with its limit types
     */
    private function configureRateLimiter(string $limiterName, array $limitTypes): void
    {
        if (! Config::get("rate-limiting.limiters.{$limiterName}.enabled", true)) {
            return;
        }

        RateLimiter::for($limiterName, function (Request $request) use ($limiterName, $limitTypes) {
            $limiterConfig = Config::get("rate-limiting.limiters.{$limiterName}");

            foreach ($limitTypes as $limitType => $keyGenerator) {
                if (! ($limiterConfig['limits'][$limitType]['enabled'] ?? false)) {
                    continue;
                }

                $key = $limiterName . ':' . $keyGenerator($request);
                $maxAttempts = $limiterConfig['limits'][$limitType]['max_attempts'];
                $growthStrategy = $limiterConfig['growth_strategy'];

                $result = $this->applyAdvancedBackoff(
                    $key,
                    $maxAttempts,
                    $limiterName,
                    $limitType,
                    $growthStrategy,
                    $request,
                );

                if ($result !== true) {
                    return $result;
                }
            }

            return null;
        });
    }

    /**
     * Apply advanced backoff with exponential or linear growth
     */
    private function applyAdvancedBackoff(
        string $key,
        int $maxAttempts,
        string $limiterType,
        string $limitType,
        string $growthStrategy,
        Request $request,
    ): Limit|true|Unlimited {
        $currentAttempts = RateLimiter::attempts($key);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $wait = RateLimiter::availableIn($key);

            // Log warning about excessive attempts if enabled
            if (Config::get('rate-limiting.log_violations', true)) {
                Log::warning("Rate limit exceeded for {$limiterType}:{$limitType}: {$key}", [
                    'wait_seconds' => $wait,
                    'attempts' => $currentAttempts,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'limiter_type' => $limiterType,
                    'limit_type' => $limitType,
                    'growth_strategy' => $growthStrategy,
                ]);
            }

            // Get custom message with enhanced information
            $message = $this->getRateLimitMessage($limiterType, $limitType, $wait, $currentAttempts);

            // Return a custom Limit that will trigger the response
            return Limit::none()->response(function () use ($message, $request) {
                return redirect()
                    ->back()
                    ->withInput($request->except(['password', 'password_confirmation', 'code']))
                    ->withErrors(['rate_limit' => $message]);
            });
        }

        // Calculate decay time based on growth strategy
        $decay = $this->calculateDecayTime($currentAttempts, $growthStrategy);

        // Increment the counter with custom decay (suspension time)
        RateLimiter::hit($key, $decay);

        // Check if user is approaching the limit and provide warning (after incrementing)
        $attemptsAfterHit = $currentAttempts + 1;
        $remainingAttempts = $maxAttempts - $attemptsAfterHit;
        if ($remainingAttempts <= 2 && $remainingAttempts > 0) {
            // Add a warning message for approaching limit
            $warningMessage = $this->getWarningMessage($limiterType, $remainingAttempts);
            session()->flash('rate_limit_warning', $warningMessage);
        }

        return true; // allow attempt
    }

    /**
     * Get a user-friendly rate limit message with enhanced information
     */
    private function getRateLimitMessage(
        string $limiterType,
        string $limitType,
        int $waitSeconds,
        int $attempts,
    ): string {
        $waitMinutes = ceil($waitSeconds / 60);

        // Get base message from config
        $message = Config::get("rate-limiting.messages.{$limiterType}.{$limitType}");

        if (! $message) {
            $message = Config::get(
                'rate-limiting.messages.default',
                'Too many attempts. Please wait :minutes minutes before trying again.',
            );
        }

        // Enhance message with suggestions based on limiter type
        $enhancedMessage = __($message, ['minutes' => $waitMinutes]);
        $suggestions = $this->getSuggestions($limiterType, $attempts);

        if ($suggestions) {
            $enhancedMessage .= ' ' . $suggestions;
        }

        return $enhancedMessage;
    }

    /**
     * Get helpful suggestions based on the limiter type and attempt count
     */
    private function getSuggestions(string $limiterType, int $attempts): string
    {
        // Check if limiter type has high/low attempt suggestions
        if (in_array($limiterType, ['login', 'two-factor'])) {
            $suggestionKey = $attempts >= 3 ? 'high_attempts' : 'low_attempts';
            $suggestion = Config::get("rate-limiting.suggestions.{$limiterType}.{$suggestionKey}");

            if ($suggestion) {
                return __($suggestion);
            }
        }

        // Get simple suggestion for other limiter types
        $suggestion = Config::get("rate-limiting.suggestions.{$limiterType}");
        if ($suggestion) {
            return __($suggestion);
        }

        // Fallback to default
        return __(Config::get('rate-limiting.suggestions.default', 'Please wait before trying again.'));
    }

    /**
     * Get warning message for users approaching rate limit
     */
    private function getWarningMessage(string $limiterType, int $remainingAttempts): string
    {
        // Get base warning message from config
        $baseMessage = Config::get(
            'rate-limiting.warning_messages.base',
            'You have :attempts attempt(s) remaining before a temporary lockout.',
        );
        $baseMessage = __($baseMessage, ['attempts' => $remainingAttempts]);

        // Get suggestion from config
        $suggestion = Config::get("rate-limiting.warning_messages.suggestions.{$limiterType}");
        if (! $suggestion) {
            $suggestion = Config::get(
                'rate-limiting.warning_messages.suggestions.default',
                'Please verify your information before continuing.',
            );
        }

        return $baseMessage . ' ' . __($suggestion);
    }

    /**
     * Calculate decay time based on growth strategy
     */
    private function calculateDecayTime(int $attempts, string $growthStrategy): int
    {
        $maxSuspensionTime = Config::get('rate-limiting.max_suspension_time', 3600);

        return match ($growthStrategy) {
            'exponential' => min($maxSuspensionTime, 60 * 2 ** $attempts),
            'fibonacci' => min($maxSuspensionTime, 60 * $this->getFibonacci($attempts + 1)),
            default => min($maxSuspensionTime, 60 * ($attempts + 1)), // Default to linear
        };
    }

    /**
     * Get Fibonacci number at position n (starting from 1, 2, 3, 5, 8, 13, 21, 34, 55...)
     * This provides a more lenient backoff than pure exponential growth
     */
    private function getFibonacci(int $n): int
    {
        if ($n <= 0) {
            return 1;
        }
        if ($n === 1) {
            return 1;
        }
        if ($n === 2) {
            return 2;
        }

        $a = 1; // F(1)
        $b = 2; // F(2)

        for ($i = 3; $i <= $n; $i++) {
            $temp = $a + $b;
            $a = $b;
            $b = $temp;
        }

        return $b;
    }
}
