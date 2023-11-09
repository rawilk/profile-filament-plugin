@props([
    'actions' => false,
])

<div {{ $attributes->class('px-4 py-3 bg-gray-100 dark:bg-gray-800 dark:text-gray-200 first:rounded-t-md') }}>
    <div class="flex justify-between items-center gap-x-3">
        <h3 class="text-base font-semibold">{{ $slot }}</h3>

        @if ($actions)
            <div>
                {{ $actions }}
            </div>
        @endif
    </div>
</div>
