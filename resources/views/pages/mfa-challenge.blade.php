<x-filament-panels::page.simple class="fi-mfa-challenge">
    <x-profile-filament::plugin-css>
        <x-filament-panels::form wire:submit="authenticate">
            <div class="gap-y-2 flex flex-col">
                @if ($this->modeIcon)
                    <div class="flex justify-center">
                        <x-filament::icon
                            :icon="$this->modeIcon"
                            class="fi-mfa-mode-icon h-8 w-8 text-gray-600 dark:text-white"
                        />
                    </div>
                @endif

                <h2 class="fi-mfa-form-label text-xl tracking-tight text-center text-gray-950 dark:text-white">{{ $this->formLabel }}</h2>
            </div>

            {{ \Filament\Support\Facades\FilamentView::renderHook('profile-filament::mfa-challenge.start') }}

            <div>
                @if ($error)
                    <div>
                        <x-profile-filament::alert
                            color="danger"
                            dismiss
                            class="mb-4"
                        >
                            {{ $error }}
                        </x-profile-filament::alert>
                    </div>
                @endif

                @if ($this->isWebauthn)
                    <x-profile-filament::webauthn-script
                        x-data="webauthnForm({
                            mode: 'login',
                            wireId: '{{ $this->getId() }}',
                            serverError: $wire.entangle('hasWebauthnError'),
                            loginPublicKeyUrl: '{{ URL::signedRoute('profile-filament::webauthn.assertion_pk', ['user' => \Rawilk\ProfileFilament\Facades\Mfa::challengedUser()->id]) }}',
                        })"
                        id="webauthn-form"
                    >
                        @include('profile-filament::livewire.partials.webauthn-unsupported')

                        <div x-show="browserSupported" x-cloak>
                            <p class="text-center text-gray-950 dark:text-white mb-4">{{ __('profile-filament::pages/mfa.webauthn.hint') }}</p>

                            <div x-show="error" x-cloak style="display: none;">
                                <template x-if="error">
                                    <p class="text-danger-500 mb-4 text-center" role="alert">
                                        {{ __('profile-filament::pages/mfa.webauthn.failed') }}
                                    </p>
                                </template>
                            </div>

                            <div x-show="! processing">
                                <x-filament-panels::form.actions
                                    :actions="[$this->getWebauthnFormAction()]"
                                    :full-width="$this->hasFullWidthFormActions()"
                                />
                            </div>

                            <div x-show="processing" style="display: none;" x-cloak>
                                <x-profile-filament::webauthn-waiting-indicator
                                    :message="__('profile-filament::pages/mfa.webauthn.waiting')"
                                />
                            </div>
                        </div>
                    </x-profile-filament::webauthn-script>
                @else
                    <div id="mfa-{{ $mode }}-form">
                        {{ $this->form }}

                        <div class="mt-4">
                            <x-filament-panels::form.actions
                                :actions="$this->getCachedFormActions()"
                                :full-width="$this->hasFullWidthFormActions()"
                            />
                        </div>
                    </div>
                @endif
            </div>
        </x-filament-panels::form>
    </x-profile-filament::plugin-css>

    @if ($this->alternativeChallengeOptions->isNotEmpty())
        <div class="border rounded-md py-3 px-2.5">
            <p class="text-sm text-gray-950 dark:text-white">{{ $this->alternativesHeading }}</p>

            <ul class="mt-2 list-disc list-inside pl-2 text-sm" role="list">
                @foreach ($this->alternativeChallengeOptions as $option)
                    <li wire:key="alt{{ $option['key'] }}">
                        <x-filament::link
                            color="primary"
                            tag="button"
                            wire:click="setMode('{{ $option['key'] }}')"
                        >
                            <span class="font-normal">{{ $option['label'] }}</span>
                        </x-filament::link>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</x-filament-panels::page.simple>
