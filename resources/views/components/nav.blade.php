<div {{ $attributes->class('lg:flex lg:gap-x-16') }}>
    <aside class="flex overflow-x-auto border-b lg:block lg:w-64 lg:flex-none lg:border-0">
        <nav class="flex-none">
            <ul role="list"
                class="flex gap-x-3 gap-y-1 whitespace-nowrap lg:flex-col"
            >
                {{ $nav }}
            </ul>
        </nav>
    </aside>

    <div class="lg:flex-auto lg:px-0 py-8 lg:py-0">
        {{ $slot }}
    </div>
</div>
