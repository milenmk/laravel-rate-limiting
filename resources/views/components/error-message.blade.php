@props(['field' => '', 'title' => null, 'class' => 'my-2 p-2'])

@if (isset($errors))
    @error($field)
        <div
            class="bg-[rgb(248, 113, 113)] border-[rgb(220, 38, 38)] {{ $class }} relative flex items-center rounded border text-white dark:bg-[rgb(231,81,90)]/15"
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
                    <div class="mt-1 text-sm">{{ $message }}</div>
                </div>
            </span>
        </div>
    @enderror
@endif
