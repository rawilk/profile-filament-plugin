@php
    use Rawilk\ProfileFilament\Enums\ProfileFilamentIcon;
@endphp

<div x-show="! browserSupportsWebAuthn" x-cloak wire:ignore>
    <div class="text-left max-w-xl text-pretty">
        <div class="flex items-center gap-x-2">
            <div>
                <x-filament::icon
                    :icon="ProfileFilamentIcon::MfaWebauthnUnsupported->resolve()"
                    class="h-6 w-6 text-danger-500"
                />
            </div>

            <h3 class="text-base">
                {{ __('profile-filament::auth/multi-factor/webauthn/provider.messages.unsupported.title') }}
            </h3>
        </div>

        <div class="mt-2 text-sm">
            {{ str(__('profile-filament::auth/multi-factor/webauthn/provider.messages.unsupported.body'))->markdown()->toHtmlString() }}
        </div>

        <div class="mt-3 text-sm">
            <x-filament::link
                href="https://webauthn.me/browser-support"
                target="_blank"
                rel="nofollow noreferrer"
            >
                {{ __('profile-filament::auth/multi-factor/webauthn/provider.messages.unsupported.learn-more-link') }}
            </x-filament::link>
        </div>
    </div>
</div>
