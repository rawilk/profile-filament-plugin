<div>
    @if ($show)
        <div class="pt-4">
            <div id="webauthn-keys-list"
                @class([
                    'mb-4 border-b border-gray-300 dark:border-gray-600' => $this->sortedWebauthnKeys->isNotEmpty(),
                    'divide-y divide-gray-300 dark:divide-gray-600',
                ])
            >
                @foreach ($this->sortedWebauthnKeys as $webauthnKey)
                    <livewire:webauthn-key
                        :webauthn-key="$webauthnKey"
                        :key="'webauthnKey' . $webauthnKey->id"
                    />
                @endforeach
            </div>

            <div>
                @unless ($showForm)
                    {{ $this->addAction }}
                @endunless

                @includeWhen($showForm, 'profile-filament::livewire.partials.webauthn-key-register-form')
            </div>
        </div>

        <x-filament-actions::modals />
    @endif
</div>
