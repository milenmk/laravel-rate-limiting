@props(['message' => '', 'title' => null, 'class' => 'my-2 p-2', 'wait_seconds' => null])

@if ($message || session('rate_limit_error'))
    @php
        $displayMessage = $message ?: session('rate_limit_error');
        $remainingSeconds = $wait_seconds ?? (session('rate_limit_wait_time') ?? null);
    @endphp

    <div
        class="{{ $class }} relative flex items-center rounded border border-[rgb(220,38,38)] bg-[rgb(248,113,113)] text-white dark:bg-[rgb(231,81,90)]/15"
    >
        <span class="flex items-center">
            <svg
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
                class="mr-3 h-6 w-6"
            >
                <circle opacity="0.5" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"></circle>
                <path d="M12 7V13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                <circle cx="12" cy="16" r="1" fill="currentColor"></circle>
            </svg>
            <div>
                <div class="font-semibold">{{ $title }}</div>
                <div class="mt-1 text-sm">{{ $displayMessage }}</div>
            </div>
        </span>
    </div>

    @if ($remainingSeconds && config('rate-limiting.show_wait_counter', true))
        <div
            class="{{ $class }} relative flex items-center rounded border border-[rgb(220,38,38)] bg-[rgb(248,113,113)] text-white dark:bg-[rgb(231,81,90)]/15"
        >
            <div class="font-mono text-sm" id="countdown">
                Remaining blocked time:
                <span>{{ gmdate('i:s', $remainingSeconds) }}</span>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                let countdownElement = document.getElementById('countdown').querySelector('span');
                let remaining = {{ $remainingSeconds }};

                function updateTimer() {
                    if (remaining > 0) {
                        remaining--;
                        let minutes = Math.floor(remaining / 60);
                        let seconds = remaining % 60;
                        countdownElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                    } else {
                        // Optionally hide or update the message once countdown is over
                        countdownElement.textContent = '0:00';
                        clearInterval(interval);
                    }
                }

                let interval = setInterval(updateTimer, 1000);
            });
        </script>
    @endif
@endif
