<div x-show="! browserSupported" x-cloak>
    <div class="text-left">
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
            {{ \Rawilk\ProfileFilament\renderMarkdown(__('profile-filament::pages/mfa.webauthn.unsupported.message')) }}
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
