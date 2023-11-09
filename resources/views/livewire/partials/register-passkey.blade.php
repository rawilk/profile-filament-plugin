<div class="-mt-3" x-data="{ isUpgrading: @js(filled($upgrading)), exclude: @js([$upgrading?->id]) }">
    <div
        x-ignore
        ax-load="visible"
        ax-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('webauthnForm', package: 'rawilk/profile-filament-plugin') }}"
        x-data="webauthnForm({
            mode: 'register',
            wireId: '{{ $this->getId() }}',
            registerPublicKeyUrl: '{{ route('profile-filament::webauthn.passkey_attestation_pk') }}',
            registerData: () => isUpgrading ? { exclude } : {},

            beforeRegister: instance => {
                if (isUpgrading) {
                    return true;
                }

                return @this.validate()
                    .then(() => ! instance.hasErrors(@this));
            },
        })"
        id="register-passkey-form"
        wire:ignore.self
    >
        <div class="text-sm text-left" x-show="browserSupported">
            @if ($upgrading)
                <p id="webauthn-key-intro-{{ $upgrading->getKey() }}">
                    {{ new \Illuminate\Support\HtmlString(
                        \Illuminate\Support\Str::inlineMarkdown(__('profile-filament::pages/security.passkeys.actions.upgrade.intro', ['name' => e($upgrading->name)]))
                    ) }}
                </p>
            @else
                <p>{{ __('profile-filament::pages/security.passkeys.actions.add.intro') }}</p>
            @endif

            <p class="mt-2">{{ __('profile-filament::pages/security.passkeys.actions.add.intro_line2') }}</p>

            @unless (filament()->auth()->user()->two_factor_enabled)
                <p class="mt-2">
                    {{ new \Illuminate\Support\HtmlString(
                        \Illuminate\Support\Str::inlineMarkdown(__('profile-filament::pages/security.passkeys.actions.add.mfa_disabled_notice'))
                    ) }}
                </p>
            @endunless
        </div>

        <div x-show="! browserSupported" class="mt-2">
            @include('profile-filament::livewire.partials.webauthn-unsupported')
        </div>

        <div x-show="browserSupported" class="w-full">
            <div
                class="mt-4"
                x-show="! processing"
            >
                {{ $this->form }}

                <div class="mt-4">
                    <div class="flex flex-col items-center mb-4"
                         x-show="error"
                         x-cloak
                         wire:ignore
                    >
                        <div class="flex items-center gap-x-2 text-danger-600 dark:text-danger-400">
                            <div>
                                <x-filament::icon
                                    alias="profile-filament::webauthn-error"
                                    icon="heroicon-o-exclamation-triangle"
                                    class="h-4 w-4"
                                />
                            </div>

                            <span class="text-sm">
                                {{ __('profile-filament::pages/security.passkeys.actions.add.register_fail') }}
                            </span>
                        </div>
                    </div>

                    <div>
                        <x-filament::button
                            color="primary"
                            x-on:click="submit"
                            class="w-full"
                        >
                            {{ $upgrading ? __('profile-filament::pages/security.passkeys.actions.upgrade.prompt_button') : __('profile-filament::pages/security.passkeys.actions.add.prompt_button') }}
                        </x-filament::button>
                    </div>

                    <div>
                        @if ($upgrading)
                            <x-filament::button
                                color="gray"
                                class="w-full mt-3"
                                x-on:click="isUpgrading = false; error = null; $wire.cancelUpgrade();"
                            >
                                {{ __('profile-filament::pages/security.passkeys.actions.upgrade.cancel_upgrade') }}
                            </x-filament::button>
                        @endif
                    </div>

                    <div class="mt-2 text-center">
                        <x-filament::link
                            tag="button"
                            color="gray"
                            x-on:click="close"
                            class="text-xs"
                        >
                            {{ __('filament-actions::modal.actions.cancel.label') }}
                        </x-filament::link>
                    </div>
                </div>
            </div>

            <x-profile-filament::webauthn-waiting-indicator
                x-show="processing"
                wire:ignore
                class="mt-4"
                :message="__('profile-filament::pages/security.mfa.webauthn.actions.register.waiting')"
            />
        </div>
    </div>
</div>
