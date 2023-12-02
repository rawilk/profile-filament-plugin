<x-filament-panels::page.simple class="fi-sudo-challenge">
    <x-profile-filament::plugin-css>
        <x-filament-panels::form wire:submit="authenticate">
            @include('profile-filament::livewire.sudo.signed-in-as')

            <div class="px-4 py-3 border dark:border-gray-500 rounded-md bg-slate-50 dark:bg-gray-800">

                @if ($this->formLabel)
                    <div class="gap-y-2 flex flex-col mb-4">
                        @if ($this->formIcon)
                            <div class="flex justify-center">
                                <x-filament::icon
                                    :icon="$this->formIcon"
                                    class="fi-mfa-mode-icon h-8 w-8 text-gray-600 dark:text-white"
                                />
                            </div>
                        @endif

                        <h2 class="fi-sudo-form-label text-xl tracking-tight text-center text-gray-950 dark:text-white">
                            {{ $this->formLabel }}
                        </h2>
                    </div>
                @endif

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
                            loginPublicKeyUrl: '{{ URL::signedRoute('profile-filament::webauthn.assertion_pk', ['user' => $this->user->id, 's' => \Rawilk\ProfileFilament\Enums\Session\SudoSession::WebauthnAssertionPk->value]) }}',
                            loginUsing: function (assertion) {
                                const component = window.Livewire.find('{{ $this->getId() }}');

                                return component.authenticate(assertion);
                            },
                            serverError: $wire.entangle('hasWebauthnError'),
                            loginMethodName: 'authenticate',
                        })"
                        id="sudo-webauthn-form"
                    >
                        @include('profile-filament::livewire.partials.webauthn-unsupported')

                        <div x-show="browserSupported" x-cloak>
                            <p class="text-center text-sm text-gray-950 dark:text-white mb-4">{{ __('profile-filament::messages.sudo_challenge.webauthn.hint') }}</p>

                            <div x-show="error">
                                <template x-if="error">
                                    <p class="text-danger-500 mb-4 text-center" role="alert">
                                        {{ __('profile-filament::messages.sudo_challenge.webauthn.failed') }}
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
                                <div class="flex items-center justify-center gap-x-2">
                                    <div>
                                        <x-filament::icon
                                            alias="mfa::webauthn-waiting"
                                            icon="heroicon-m-arrow-path"
                                            class="h-4 w-4 animate-spin"
                                        />
                                    </div>

                                    <p>{{ __('profile-filament::messages.sudo_challenge.webauthn.waiting') }}</p>
                                </div>
                            </div>
                        </div>
                    </x-profile-filament::webauthn-script>
                @else
                    <div id="sudo-{{ $sudoChallengeMode }}-form">
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

    @if ($this->alternateChallengeOptions->isNotEmpty())
        <div class="border rounded-md py-3 px-2.5">
            <p class="text-sm text-gray-950 dark:text-white">{{ __('profile-filament::messages.sudo_challenge.alternative_heading') }}</p>

            <ul class="mt-2 list-disc list-inside pl-2 text-sm" role="list">
                @foreach ($this->alternateChallengeOptions as $option)
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

    <p class="text-center text-xs">
        {{ \Rawilk\ProfileFilament\renderMarkdown(__('profile-filament::messages.sudo_challenge.tip')) }}
    </p>
</x-filament-panels::page.simple>
