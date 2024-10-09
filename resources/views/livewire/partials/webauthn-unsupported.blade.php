<div x-show="! browserSupportsWebAuthn" x-cloak wire:ignore>
    <div class="text-left max-w-xl text-pretty">
        <div class="flex items-center gap-x-2">
            <div>
                <x-filament::icon
                    alias="mfa::webauthn-unsupported"
                    icon="heroicon-o-exclamation-circle"
                    class="h-6 w-6 text-danger-500"
                />
            </div>

            <h3 class="text-base">
                {{ __('profile-filament::pages/mfa.webauthn.unsupported.title') }}
            </h3>
        </div>

        <div class="mt-2 text-sm">
            {{ str(__('profile-filament::pages/mfa.webauthn.unsupported.message'))->markdown()->toHtmlString() }}
        </div>

        <div class="mt-3 text-sm">
            <x-filament::link
                href="https://webauthn.me/browser-support"
                target="_blank"
                rel="nofollow noreferrer"
            >
                {{ __('profile-filament::pages/mfa.webauthn.unsupported.learn_more_link') }}
            </x-filament::link>
        </div>
    </div>
</div>
