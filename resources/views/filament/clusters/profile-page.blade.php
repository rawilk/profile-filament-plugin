<x-filament-panels::page>
    <x-profile-filament::plugin-css
        class="flex flex-col gap-y-6 lg:gap-y-10"
    >
        @foreach ($this->registeredComponents as $component)
            @livewire($component)
        @endforeach
    </x-profile-filament::plugin-css>
</x-filament-panels::page>
