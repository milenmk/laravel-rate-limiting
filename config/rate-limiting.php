<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for advanced rate limiting with
    | exponential/linear backoff and suspension capabilities.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Global Rate Limiting Settings
    |--------------------------------------------------------------------------
    |
    | These settings control the overall behavior of the rate limiting system.
    |
    */
    'enabled' => env('RATE_LIMITING_ENABLED', true),
    'log_violations' => env('RATE_LIMITING_LOG_VIOLATIONS', true),
    'max_suspension_time' => env('RATE_LIMITING_MAX_SUSPENSION_TIME', 3600), // 1 hour in seconds

    /*
    |--------------------------------------------------------------------------
    | Rate Limiters Configuration
    |--------------------------------------------------------------------------
    |
    | Configure each rate limiter individually. Each limiter can be enabled/disabled
    | and configured with different growth strategies and limits. See `docs/RATE_LIMITING.md`
    |
    */
    'limiters' => [
        'register' => [
            'enabled' => env('RATE_LIMITING_REGISTER_ENABLED', true),
            'growth_strategy' => env('RATE_LIMITING_REGISTER_GROWTH', 'linear'), // 'linear', 'fibonacci', or 'exponential' (2^n)
            'limits' => [
                'global' => [
                    'enabled' => env('RATE_LIMITING_REGISTER_GLOBAL_ENABLED', true),
                    'max_attempts' => env('RATE_LIMITING_REGISTER_GLOBAL_MAX_ATTEMPTS', 150),
                ],
                'email' => [
                    'enabled' => env('RATE_LIMITING_REGISTER_EMAIL_ENABLED', true),
                    'max_attempts' => env('RATE_LIMITING_REGISTER_EMAIL_MAX_ATTEMPTS', 3),
                ],
                'ip' => [
                    'enabled' => env('RATE_LIMITING_REGISTER_IP_ENABLED', true),
                    'max_attempts' => env('RATE_LIMITING_REGISTER_IP_MAX_ATTEMPTS', 3),
                ],
            ],
        ],

        'login' => [
            'enabled' => env('RATE_LIMITING_LOGIN_ENABLED', true),
            'growth_strategy' => env('RATE_LIMITING_LOGIN_GROWTH', 'linear'), // 'linear', 'fibonacci', or 'exponential' (2^n)
            'limits' => [
                'global' => [
                    'enabled' => env('RATE_LIMITING_LOGIN_GLOBAL_ENABLED', false), // Disabled by default for login
                    'max_attempts' => env('RATE_LIMITING_LOGIN_GLOBAL_MAX_ATTEMPTS', 1000),
                ],
                'username_ip' => [
                    'enabled' => env('RATE_LIMITING_LOGIN_USERNAME_IP_ENABLED', true),
                    'max_attempts' => env('RATE_LIMITING_LOGIN_USERNAME_IP_MAX_ATTEMPTS', 5),
                ],
                'ip' => [
                    'enabled' => env('RATE_LIMITING_LOGIN_IP_ENABLED', true),
                    'max_attempts' => env('RATE_LIMITING_LOGIN_IP_MAX_ATTEMPTS', 10),
                ],
            ],
        ],

        'forgot-password' => [
            'enabled' => env('RATE_LIMITING_FORGOT_PASSWORD_ENABLED', true),
            'growth_strategy' => env('RATE_LIMITING_FORGOT_PASSWORD_GROWTH', 'linear'), // 'linear', 'fibonacci', or 'exponential' (2^n)
            'limits' => [
                'global' => [
                    'enabled' => env('RATE_LIMITING_FORGOT_PASSWORD_GLOBAL_ENABLED', false), // Disabled by default
                    'max_attempts' => env('RATE_LIMITING_FORGOT_PASSWORD_GLOBAL_MAX_ATTEMPTS', 500),
                ],
                'email' => [
                    'enabled' => env('RATE_LIMITING_FORGOT_PASSWORD_EMAIL_ENABLED', true),
                    'max_attempts' => env('RATE_LIMITING_FORGOT_PASSWORD_EMAIL_MAX_ATTEMPTS', 3),
                ],
                'ip' => [
                    'enabled' => env('RATE_LIMITING_FORGOT_PASSWORD_IP_ENABLED', true),
                    'max_attempts' => env('RATE_LIMITING_FORGOT_PASSWORD_IP_MAX_ATTEMPTS', 5),
                ],
            ],
        ],

        'two-factor' => [
            'enabled' => env('RATE_LIMITING_TWO_FACTOR_ENABLED', true),
            'growth_strategy' => env('RATE_LIMITING_TWO_FACTOR_GROWTH', 'fibonacci'), // 'linear', 'fibonacci', or 'exponential' (2^n)
            'limits' => [
                'global' => [
                    'enabled' => env('RATE_LIMITING_TWO_FACTOR_GLOBAL_ENABLED', false), // Disabled by default
                    'max_attempts' => env('RATE_LIMITING_TWO_FACTOR_GLOBAL_MAX_ATTEMPTS', 1000),
                ],
                'session' => [
                    'enabled' => env('RATE_LIMITING_TWO_FACTOR_SESSION_ENABLED', true),
                    'max_attempts' => env('RATE_LIMITING_TWO_FACTOR_SESSION_MAX_ATTEMPTS', 5),
                ],
                'ip' => [
                    'enabled' => env('RATE_LIMITING_TWO_FACTOR_IP_ENABLED', true),
                    'max_attempts' => env('RATE_LIMITING_TWO_FACTOR_IP_MAX_ATTEMPTS', 10),
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Messages
    |--------------------------------------------------------------------------
    |
    | Customize the messages shown to users when rate limits are exceeded.
    | Use :minutes placeholder for wait time in minutes.
    |
    */
    'messages' => [
        'register' => [
            'global' => env(
                'RATE_LIMITING_REGISTER_GLOBAL_MESSAGE',
                'Too many registration attempts across the system. Please wait :minutes minutes before trying again.',
            ),
            'email' => env(
                'RATE_LIMITING_REGISTER_EMAIL_MESSAGE',
                'Too many registration attempts with this email address. Please wait :minutes minutes before trying again.',
            ),
            'ip' => env(
                'RATE_LIMITING_REGISTER_IP_MESSAGE',
                'Too many registration attempts from your location. Please wait :minutes minutes before trying again.',
            ),
        ],
        'login' => [
            'global' => env(
                'RATE_LIMITING_LOGIN_GLOBAL_MESSAGE',
                'Too many login attempts across the system. Please wait :minutes minutes before trying again.',
            ),
            'username_ip' => env(
                'RATE_LIMITING_LOGIN_USERNAME_IP_MESSAGE',
                'Too many login attempts with this username from your location. Please wait :minutes minutes before trying again.',
            ),
            'ip' => env(
                'RATE_LIMITING_LOGIN_IP_MESSAGE',
                'Too many login attempts from your location. Please wait :minutes minutes before trying again.',
            ),
        ],
        'forgot-password' => [
            'global' => env(
                'RATE_LIMITING_FORGOT_PASSWORD_GLOBAL_MESSAGE',
                'Too many password reset attempts across the system. Please wait :minutes minutes before trying again.',
            ),
            'email' => env(
                'RATE_LIMITING_FORGOT_PASSWORD_EMAIL_MESSAGE',
                'Too many password reset attempts for this email address. Please wait :minutes minutes before trying again.',
            ),
            'ip' => env(
                'RATE_LIMITING_FORGOT_PASSWORD_IP_MESSAGE',
                'Too many password reset attempts from your location. Please wait :minutes minutes before trying again.',
            ),
        ],
        'two-factor' => [
            'global' => env(
                'RATE_LIMITING_TWO_FACTOR_GLOBAL_MESSAGE',
                'Too many two-factor authentication attempts across the system. Please wait :minutes minutes before trying again.',
            ),
            'session' => env(
                'RATE_LIMITING_TWO_FACTOR_SESSION_MESSAGE',
                'Too many two-factor authentication attempts for this session. Please wait :minutes minutes before trying again.',
            ),
            'ip' => env(
                'RATE_LIMITING_TWO_FACTOR_IP_MESSAGE',
                'Too many two-factor authentication attempts from your location. Please wait :minutes minutes before trying again.',
            ),
        ],
        'default' => env(
            'RATE_LIMITING_DEFAULT_MESSAGE',
            'Too many attempts. Please wait :minutes minutes before trying again.',
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Warning Messages
    |--------------------------------------------------------------------------
    |
    | Messages shown to users when they are approaching rate limits.
    | Use :attempts placeholder for remaining attempts count.
    |
    */
    'warning_messages' => [
        'base' => env(
            'RATE_LIMITING_WARNING_BASE_MESSAGE',
            'You have :attempts attempt(s) remaining before a temporary lockout.',
        ),
        'suggestions' => [
            'login' => env(
                'RATE_LIMITING_WARNING_LOGIN_SUGGESTION',
                'If you\'ve forgotten your password, consider using the "Reset Password" link below.',
            ),
            'register' => env(
                'RATE_LIMITING_WARNING_REGISTER_SUGGESTION',
                'Please double-check your information before submitting.',
            ),
            'forgot-password' => env(
                'RATE_LIMITING_WARNING_FORGOT_PASSWORD_SUGGESTION',
                'Please verify the email address is correct.',
            ),
            'two-factor' => env(
                'RATE_LIMITING_WARNING_TWO_FACTOR_SUGGESTION',
                'Double-check your authenticator app or recovery code.',
            ),
            'default' => env(
                'RATE_LIMITING_WARNING_DEFAULT_SUGGESTION',
                'Please verify your information before continuing.',
            ),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Suggestion Messages
    |--------------------------------------------------------------------------
    |
    | Helpful suggestions shown to users when rate limits are exceeded.
    | These are appended to the main error messages.
    |
    */
    'suggestions' => [
        'login' => [
            'high_attempts' => env(
                'RATE_LIMITING_LOGIN_HIGH_ATTEMPTS_SUGGESTION',
                'Consider resetting your password if you\'ve forgotten it, or contact support if you believe this is an error.',
            ),
            'low_attempts' => env(
                'RATE_LIMITING_LOGIN_LOW_ATTEMPTS_SUGGESTION',
                'Please double-check your email and password.',
            ),
        ],
        'register' => env(
            'RATE_LIMITING_REGISTER_SUGGESTION',
            'Please verify all required fields are filled correctly and try again later.',
        ),
        'forgot-password' => env(
            'RATE_LIMITING_FORGOT_PASSWORD_SUGGESTION',
            'Please ensure the email address is correct and check your spam folder for previous reset emails.',
        ),
        'two-factor' => [
            'high_attempts' => env(
                'RATE_LIMITING_TWO_FACTOR_HIGH_ATTEMPTS_SUGGESTION',
                'If you\'re having trouble with your authenticator, try using a recovery code instead.',
            ),
            'low_attempts' => env(
                'RATE_LIMITING_TWO_FACTOR_LOW_ATTEMPTS_SUGGESTION',
                'Please check your authenticator app for the current code.',
            ),
        ],
        'default' => env('RATE_LIMITING_DEFAULT_SUGGESTION', 'Please wait before trying again.'),
    ],
];
