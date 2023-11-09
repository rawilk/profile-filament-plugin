<div>
    <div class="text-sm mb-4">
        {{ new \Illuminate\Support\HtmlString(\Illuminate\Support\Str::markdown(__('profile-filament::pages/security.passkeys.list.description'))) }}
    </div>

    <div id="passkeys-list" class="border rounded-md dark:border-gray-700 divide-y dark:divide-gray-700">
        <x-profile-filament::box-header id="header-{{ $headerId }}">
            {{ __('profile-filament::pages/security.passkeys.list.title') }}

            <x-slot:actions>
                {{ $this->addAction }}
            </x-slot:actions>
        </x-profile-filament::box-header>

        <ul aria-labelledby="header-{{ $headerId }}"
            role="list"
            class="divide-y dark:divide-gray-700"
        >
            @foreach ($passkeys as $passkey)
                <li class="px-4 py-3" wire:key="passkey{{ $passkey->id }}">
                    <livewire:passkey :passkey="$passkey" :key="$passkey->id" />
                </li>
            @endforeach
        </ul>
    </div>
</div>
