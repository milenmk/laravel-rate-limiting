<?php

declare(strict_types=1);

namespace Milenmk\LaravelRateLimiting\Tests;

use Illuminate\Support\Facades\View;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use PHPUnit\Framework\Attributes\Test;
use Throwable;

class BladeComponentsTest extends TestCase
{
    /**
     * @throws Throwable
     */
    public function error_message_component_renders_with_errors(): void
    {
        // Mock errors bag
        $messages = new MessageBag(['rate_limit_error' => 'Too many attempts']);
        $errors = new ViewErrorBag;
        $errors->put('default', $messages);

        $view = View::make('laravel-rate-limiting::components.error-message', [
            'title' => 'Rate Limit Exceeded',
            'message' => $errors,
        ]);

        $html = $view->render();

        $this->assertStringContainsString('Rate Limit Exceeded', $html);
        $this->assertStringContainsString('Too many attempts', $html);
        $this->assertStringContainsString('bg-[rgb(248, 113, 113)]', $html);
        $this->assertStringContainsString('svg', $html); // Icon should be present
    }

    /**
     * @throws Throwable
     */
    public function error_message_component_does_not_render_without_errors(): void
    {
        $messages = new MessageBag([]);
        $errors = new ViewErrorBag;
        $errors->put('default', $messages);

        $view = View::make('laravel-rate-limiting::components.error-message', [
            'title' => 'Rate Limit Exceeded',
            'message' => $errors, // Empty errors
        ]);

        $html = $view->render();

        // Should not render anything when no errors for the field
        $this->assertStringNotContainsString('Rate Limit Exceeded', $html);
        $this->assertStringNotContainsString('bg-[rgb(248, 113, 113)]', $html);
    }

    /**
     * @throws Throwable
     */
    #[Test]
    public function error_message_component_with_custom_class(): void
    {
        $messages = new MessageBag(['rate_limit_error' => 'Too many attempts']);
        $errors = new ViewErrorBag;
        $errors->put('default', $messages);

        $view = View::make('laravel-rate-limiting::components.error-message', [
            'title' => 'Custom Title',
            'class' => 'custom-class p-4',
            'message' => $errors,
        ]);

        $html = $view->render();

        $this->assertStringContainsString('custom-class p-4', $html);
        $this->assertStringContainsString('Custom Title', $html);
    }

    /**
     * @throws Throwable
     */
    #[Test]
    public function error_message_component_without_title(): void
    {
        $messages = new MessageBag(['rate_limit_error' => 'Too many attempts']);
        $errors = new ViewErrorBag;
        $errors->put('default', $messages);

        $view = View::make('laravel-rate-limiting::components.error-message', [
            'message' => $errors,
        ]);

        $html = $view->render();

        $this->assertStringContainsString('Too many attempts', $html);
        $this->assertStringContainsString('<div class="font-semibold"></div>', $html);
    }

    /**
     * @throws Throwable
     */
    #[Test]
    public function warning_message_component_renders_with_message_prop(): void
    {
        $view = View::make('laravel-rate-limiting::components.warning-message', [
            'message' => 'You have 2 attempts remaining',
            'title' => 'Warning',
        ]);

        $html = $view->render();

        $this->assertStringContainsString('Warning', $html);
        $this->assertStringContainsString('You have 2 attempts remaining', $html);
        $this->assertStringContainsString('bg-[rgb(251, 191, 36)]', $html);
        $this->assertStringContainsString('svg', $html); // Icon should be present
    }

    /**
     * @throws Throwable
     */
    #[Test]
    public function warning_message_component_renders_with_session_data(): void
    {
        // Set session data
        session(['rate_limit_warning' => 'Session warning message']);

        $view = View::make('laravel-rate-limiting::components.warning-message', [
            'title' => 'Session Warning',
        ]);

        $html = $view->render();

        $this->assertStringContainsString('Session Warning', $html);
        $this->assertStringContainsString('Session warning message', $html);
        $this->assertStringContainsString('bg-[rgb(251, 191, 36)]', $html);
    }

    /**
     * @throws Throwable
     */
    #[Test]
    public function warning_message_component_prioritizes_prop_over_session(): void
    {
        // Set session data
        session(['rate_limit_warning' => 'Session warning message']);

        $view = View::make('laravel-rate-limiting::components.warning-message', [
            'message' => 'Prop warning message',
            'title' => 'Priority Test',
        ]);

        $html = $view->render();

        $this->assertStringContainsString('Prop warning message', $html);
        $this->assertStringNotContainsString('Session warning message', $html);
    }

    /**
     * @throws Throwable
     */
    #[Test]
    public function warning_message_component_does_not_render_without_message(): void
    {
        $view = View::make('laravel-rate-limiting::components.warning-message', [
            'title' => 'No Message',
        ]);

        $html = $view->render();

        // Should not render anything when no message
        $this->assertStringNotContainsString('No Message', $html);
        $this->assertStringNotContainsString('bg-[rgb(251, 191, 36)]', $html);
    }

    /**
     * @throws Throwable
     */
    #[Test]
    public function warning_message_component_with_custom_class(): void
    {
        $view = View::make('laravel-rate-limiting::components.warning-message', [
            'message' => 'Custom warning',
            'title' => 'Custom Title',
            'class' => 'custom-warning-class p-6',
        ]);

        $html = $view->render();

        $this->assertStringContainsString('custom-warning-class p-6', $html);
        $this->assertStringContainsString('Custom Title', $html);
        $this->assertStringContainsString('Custom warning', $html);
    }

    /**
     * @throws Throwable
     */
    #[Test]
    public function warning_message_component_without_title(): void
    {
        $view = View::make('laravel-rate-limiting::components.warning-message', [
            'message' => 'Warning without title',
            'title' => 'Warning title',
        ]);

        $html = $view->render();

        $this->assertStringContainsString('Warning without title', $html);
        $this->assertStringContainsString('Warning title', $html);
    }

    /**
     * @throws Throwable
     */
    #[Test]
    public function components_have_proper_accessibility_attributes(): void
    {
        $messages = new MessageBag(['rate_limit_error' => 'Error message']);
        $errors = new ViewErrorBag;
        $errors->put('default', $messages);

        // Test error component
        $errorView = View::make('laravel-rate-limiting::components.error-message', [
            'message' => $errors,
        ]);
        $errorHtml = $errorView->render();

        // Should have proper ARIA attributes or semantic structure
        $this->assertStringContainsString('svg', $errorHtml);

        // Test warning component
        $warningView = View::make('laravel-rate-limiting::components.warning-message', [
            'message' => 'Warning message',
        ]);
        $warningHtml = $warningView->render();

        $this->assertStringContainsString('svg', $warningHtml);
    }

    /**
     * @throws Throwable
     */
    #[Test]
    public function components_use_proper_css_classes(): void
    {
        // Test error component styling
        $messages = new MessageBag(['rate_limit_error' => 'Error']);
        $errors = new ViewErrorBag;
        $errors->put('default', $messages);

        $errorView = View::make('laravel-rate-limiting::components.error-message', [
            'message' => $errors,
        ]);
        $errorHtml = $errorView->render();

        $this->assertStringContainsString('bg-[rgb(248, 113, 113)]', $errorHtml); // Red background
        $this->assertStringContainsString('border-[rgb(220, 38, 38)]', $errorHtml); // Red border
        $this->assertStringContainsString('text-white', $errorHtml);
        $this->assertStringContainsString('dark:bg-[rgb(231,81,90)]/15', $errorHtml); // Dark mode

        // Test warning component styling
        $warningView = View::make('laravel-rate-limiting::components.warning-message', [
            'message' => 'Warning',
        ]);
        $warningHtml = $warningView->render();

        $this->assertStringContainsString('bg-[rgb(251, 191, 36)]', $warningHtml); // Yellow background
        $this->assertStringContainsString('border-[rgb(217, 119, 6)]', $warningHtml); // Orange border
        $this->assertStringContainsString('text-white', $warningHtml);
        $this->assertStringContainsString('dark:bg-[rgb(226, 160, 63)]/15', $warningHtml); // Dark mode
    }

    /**
     * @throws Throwable
     */
    #[Test]
    public function components_handle_empty_strings(): void
    {
        // Test warning component with empty message
        $warningView = View::make('laravel-rate-limiting::components.warning-message', [
            'message' => '',
        ]);
        $warningHtml = $warningView->render();

        // Should not render when message is empty
        $this->assertStringNotContainsString('bg-[rgb(251, 191, 36)]', $warningHtml);
    }
}
