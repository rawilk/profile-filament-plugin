<x-profile-filament::layout>
    <div class="flex flex-col gap-y-6 lg:gap-y-10">
        @foreach ($this->registeredComponents as $component)
            @livewire($component)
        @endforeach
    </div>
</x-profile-filament::layout>
