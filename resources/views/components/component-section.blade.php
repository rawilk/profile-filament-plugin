@props([
    'title' => '',
    'actions' => null,
])

<div @class([
    'pb-3 border-b border-gray-300 dark:border-gray-500',
    'flex justify-between items-center gap-x-4' => filled($actions),
])>
    <h3 class="text-lg font-semibold tracking-tight text-gray-950 dark:text-white">{{ $title }}</h3>

    @if ($actions)
        <div>{{ $actions }}</div>
    @endif
</div>

<div class="mt-4">
    {{ $slot }}
</div>
