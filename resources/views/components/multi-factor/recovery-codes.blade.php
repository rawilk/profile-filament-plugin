@props(['recoveryCodes', 'actions' => null])

<div {{ $attributes->class(['pf-recovery-codes border border-gray-200 dark:border-gray-700 rounded-t-md py-6 sm:py-8 px-6 bg-gray-50 dark:bg-gray-800']) }}>
    <ol class="sm:columns-2 sm:gap-x-6 font-mono list-inside list-decimal space-y-1.5 ms-3"
        role="list"
    >
        @foreach ($recoveryCodes as $code)
            <li class="text-gray-400">
                <span class="text-neutral-600 dark:text-neutral-100">{{ $code }}</span>
            </li>
        @endforeach
    </ol>
</div>

@if ($actions)
    <div class="pf-recovery-codes-actions border border-t-0 border-gray-200 dark:border-gray-700 rounded-b-md py-3 px-4">
        <div class="flex gap-x-3 justify-end">
            @foreach ($actions as $action)
                {{ $action }}
            @endforeach
        </div>
    </div>
@endif
