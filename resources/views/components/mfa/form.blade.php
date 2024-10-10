@props([
    'alternateChallengeOptions' => collect(),
    'challengeMode' => null,
    'error' => null,
    'form' => null,
    'user' => null,
])

@php
    use Rawilk\ProfileFilament\Enums\Livewire\MfaChallengeMode;
    use Rawilk\ProfileFilament\Enums\Session\MfaSession;

    /** @var MfaChallengeMode $challengeMode */
@endphp

<x-profile-filament::plugin-css :attributes="$attributes->class(['pf-mfa-challenge'])">
    <div wire:key="{{ $this->getId() }}.mfa.{{ $challengeMode->value }}">

        {{-- heading --}}
        <div class="gap-y-2 flex flex-col">
            @if ($icon = $challengeMode->icon())
                <div class="shrink-0 flex justify-center">
                    <x-filament::icon
                        :icon="$icon"
                        class="fi-mfa-mode-icon h-8 w-8 text-gray-600 dark:text-white"
                    />
                </div>
            @endif

            <h2 class="fi-mfa-form-label text-xl tracking-tight text-center text-gray-950 dark:text-white flex-1">
                {{ $challengeMode->formLabel($user) }}
            </h2>
        </div>

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Rawilk\ProfileFilament\Enums\RenderHook::MfaChallengeStart->value) }}

        {{-- form --}}
        <div class="mt-4">

            {{-- error --}}
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

            @if ($challengeMode === MfaChallengeMode::Webauthn)
                <x-profile-filament::webauthn-script
                    mode="authenticate"
                    x-data="authenticateWebauthn({
                        publicKeyUrl: {{ Js::from($this->webauthnOptionsUrl()) }},
                    })"
                    :id="$this->getId() . '.webauthnAuthenticate'"
                >
                    @include('profile-filament::livewire.partials.webauthn-unsupported')

                    <div x-show="browserSupportsWebAuthn" x-cloak>
                        <p class="text-center text-sm text-gray-950 dark:text-white mb-4">{{ __('profile-filament::pages/mfa.webauthn.hint') }}</p>

                        <div x-show="error" wire:ignore>
                            <template x-if="error">
                                <p class="text-danger-500 mb-4 text-center" role="alert">
                                    {{ __('profile-filament::pages/mfa.webauthn.failed') }}
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
                            :message="__('profile-filament::pages/mfa.webauthn.waiting')"
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

        {{-- alternate challenges --}}
        @if ($alternateChallengeOptions->isNotEmpty())
            <div class="border dark:border-gray-500 rounded-md py-3 px-2.5 mt-4">
                <p class="text-sm text-gray-950 dark:text-white">{{ $challengeMode->alternativeHeading() }}</p>

                <ul
                    class="mt-2 list-disc list-inside pl-2 text-sm"
                    role="list"
                >
                    @foreach ($alternateChallengeOptions as $option)
                        <li
                            wire:key="mfaAlt.{{ $option['mode']->value }}"
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
    </div>
</x-profile-filament::plugin-css>
