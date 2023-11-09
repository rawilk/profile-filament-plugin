@php
    use Rawilk\ProfileFilament\Enums\Livewire\SudoChallengeMode;
    use Rawilk\ProfileFilament\Enums\Session\SudoSession;
    use Illuminate\Support\HtmlString;
    use Illuminate\Support\Js;
    use Illuminate\Support\Str;
@endphp

<div class="-mt-3">
    @include('profile-filament::livewire.sudo.signed-in-as')

    <div class="mt-4 px-4 py-3 border dark:border-gray-500 rounded-md bg-slate-50 dark:bg-gray-800">

        @if ($sudoModeHeading = $this->sudoChallengeModeEnum?->heading($user))
            <div class="gap-y-2 flex flex-col mb-4">
                @if ($sudoModeIcon = $this->sudoChallengeModeEnum?->icon())
                    <div class="flex justify-center">
                        <x-filament::icon
                            :icon="$sudoModeIcon"
                            class="fi-mfa-mode-icon h-8 w-8 text-gray-600 dark:text-white"
                        />
                    </div>
                @endif

                <h2 class="fi-sudo-form-label text-xl tracking-tight text-center text-gray-950 dark:text-white">
                    {{ $sudoModeHeading }}
                </h2>
            </div>
        @endif

        @if ($sudoError)
            <div>
                <x-profile-filament::alert
                    color="danger"
                    dismiss
                    class="mb-4"
                >
                    {{ $sudoError }}
                </x-profile-filament::alert>
            </div>
        @endif

        @if ($this->sudoChallengeModeEnum === SudoChallengeMode::Webauthn)
            <div
                x-ignore
                ax-load="visible"
                ax-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('webauthnForm', package: 'rawilk/profile-filament-plugin') }}"
                x-data="webauthnForm({
                    mode: 'login',
                    wireId: '{{ $this->getId() }}',
                    loginPublicKeyUrl: '{{ URL::signedRoute('profile-filament::webauthn.assertion_pk', ['user' => $user->id, 's' => SudoSession::WebauthnAssertionPk->value]) }}',
                    loginUsing: function (assertion) {
                        return @this.callMountedAction({ method: 'confirm', assertion });
                    },
                    serverError: $wire.entangle('hasSudoWebauthnError'),
                })"
                id="sudo-webauthn-form"
            >
                @include('profile-filament::livewire.partials.webauthn-unsupported')

                <div x-show="browserSupported">
                    <p class="text-center text-sm text-gray-950 dark:text-white mb-4">{{ __('profile-filament::messages.sudo_challenge.webauthn.hint') }}</p>

                    <div x-show="error">
                        <template x-if="error">
                            <p class="text-danger-500 mb-4 text-center" role="alert">
                                {{ __('profile-filament::messages.sudo_challenge.webauthn.failed') }}
                            </p>
                        </template>
                    </div>

                    <div x-show="! processing">
                        <x-filament::button
                            color="primary"
                            class="w-full"
                            x-on:click="submit"
                        >
                            @if ($this->hasSudoWebauthnError)
                                {{ $user->hasPasskeys() ? __('profile-filament::messages.sudo_challenge.webauthn.retry_including_passkeys') : __('profile-filament::messages.sudo_challenge.webauthn.retry') }}
                            @else
                                {{ $user->hasPasskeys() ? __('profile-filament::messages.sudo_challenge.webauthn.submit_including_passkeys') : __('profile-filament::messages.sudo_challenge.webauthn.submit') }}
                            @endif
                        </x-filament::button>
                    </div>

                    <div x-show="processing" style="display: none;" x-cloak>
                        <x-profile-filament::webauthn-waiting-indicator
                            :message="__('profile-filament::messages.sudo_challenge.webauthn.waiting')"
                        />
                    </div>
                </div>
            </div>
        @else
            <div>
                {{ $sudoForm }}

                <div class="mt-4">
                    <x-filament::button
                        color="primary"
                        class="w-full"
                        wire:click="callMountedAction({{ Js::from(['method' => 'confirm']) }})"
                    >
                        {{ $this->sudoChallengeModeEnum?->actionButton($user) ?? __('profile-filament::messages.sudo_challenge.password.submit') }}
                    </x-filament::button>
                </div>
            </div>
        @endif

    </div>

    @if ($alternateChallengeOptions->isNotEmpty())
        <div class="border dark:border-gray-500 rounded-md py-3 px-2 5 mt-4">
            <p class="text-sm text-gray-950 dark:text-white">{{ __('profile-filament::messages.sudo_challenge.alternative_heading') }}</p>

            <ul class="mt-2 list-disc list-inside pl-2 text-sm">
                @foreach ($alternateChallengeOptions as $option)
                    <li wire:key="sudoAlt{{ $option['key'] }}">
                        <x-filament::link
                            color="primary"
                            tag="button"
                            wire:click="callMountedAction({{ Js::from(['mode' => $option['key']]) }})"
                        >
                            <span class="font-normal">{{ $option['label'] }}</span>
                        </x-filament::link>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <p class="mt-4 text-left text-xs">
        {{ \Rawilk\ProfileFilament\renderMarkdown(__('profile-filament::messages.sudo_challenge.tip')) }}
    </p>
</div>
