@props([
    'alternateChallengeOptions' => collect(),
    'challengeMode' => null,
    'error' => null,
    'form' => null,
    'user' => null,
    'userHandle' => null,
])

@php
    use Rawilk\ProfileFilament\Enums\Livewire\SudoChallengeMode;
    use Rawilk\ProfileFilament\Enums\Session\SudoSession;

    /** @var SudoChallengeMode $challengeMode */
@endphp

<x-profile-filament::plugin-css :attributes="$attributes->class(['pf-sudo-form pf-sudo-modal-content'])">
    <div class="-mt-3" wire:key="{{ $this->getId() }}sudo.{{ $challengeMode->value }}">
        <x-profile-filament::sudo.signed-in-as :user-handle="$userHandle" />

        <div class="mt-4 px-4 py-3 border dark:border-gray-500 rounded-md bg-gray-50 dark:bg-gray-800">
            @if ($heading = $challengeMode?->heading($user))
                <div class="gap-y-2 flex flex-col mb-4">
                    @if ($icon = $challengeMode?->icon())
                        <div class="flex justify-center">
                            <x-filament::icon
                                :icon="$icon"
                                class="fi-mda-mode-icon h-8 w-8 text-gray-600 dark:text-white"
                            />
                        </div>
                    @endif

                    <h2 class="fi-sudo-form-label text-xl tracking-tight text-center text-gray-950 dark:text-white">
                        {{ $heading }}
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

            @if ($challengeMode === SudoChallengeMode::Webauthn)
                <x-profile-filament::webauthn-script
                    mode="authenticate"
                    x-data="authenticateWebauthn({
                        publicKeyUrl: {{ Js::from($this->webauthnOptionsUrl()) }},
                        loginMethod: 'confirm',
                    })"
                    :id="$this->getId() . '.webauthnAuthenticate'"
                    wire:ignore.self
                >
                    @include('profile-filament::livewire.partials.webauthn-unsupported')

                    <div x-show="browserSupportsWebAuthn" x-cloak>
                        <p class="text-center text-sm text-gray-950 dark:text-white mb-4" wire:ignore>{{ __('profile-filament::messages.sudo_challenge.webauthn.hint') }}</p>

                        <div x-show="error" wire:ignore>
                            <template x-if="error">
                                <p class="text-danger-500 mb-4 text-center" role="alert">
                                    {{ __('profile-filament::messages.sudo_challenge.webauthn.failed') }}
                                </p>
                            </template>
                        </div>

                        <div x-show="! processing">
                            {{ $this->startWebauthnAction }}
                        </div>

                        <x-profile-filament::webauthn-waiting-indicator
                            x-show="processing"
                            style="display: none;"
                            x-cloak
                            wire:ignore
                            :message="__('profile-filament::messages.sudo_challenge.webauthn.waiting')"
                        />
                    </div>
                </x-profile-filament::webauthn-script>
            @else
                <div>
                    {{ $form }}

                    <div class="mt-4">
                        {{ $this->submitAction }}
                    </div>
                </div>
            @endif
        </div>

        @if ($alternateChallengeOptions->isNotEmpty())
            <div class="border dark:border-gray-500 rounded-md py-3 px-2.5 mt-4">
                <p class="text-sm text-gray-950 dark:text-white">{{ __('profile-filament::messages.sudo_challenge.alternative_heading') }}</p>

                <ul class="mt-2 list-disc list-inside pl-2 text-sm" role="list">
                    @foreach ($alternateChallengeOptions as $option)
                        <li
                            wire:key="sudoAlt.{{ $option['mode']->value }}"
                        >
                            <x-filament::link
                                color="primary"
                                tag="button"
                                wire:click="setChallengeMode({{ Js::from($option['mode']->value) }})"
                            >
                                <span class="font-normal">{{ $option['label'] }}</span>
                            </x-filament::link>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="pf-sudo-tip mt-4 text-left text-xs">
            {{ str(__('profile-filament::messages.sudo_challenge.tip'))->inlineMarkdown()->toHtmlString() }}
        </div>
    </div>
</x-profile-filament::plugin-css>
