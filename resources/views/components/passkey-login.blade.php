<div
    @include('profile-filament::partials.multi-factor.webauthn.passkey-script')
    x-show="isSupported"
    x-cloak
>
    <form id="passkey-login-form" method="POST" action="{{ route('profile-filament::webauthn.passkey_authentication') }}">
        @csrf
    </form>

    <x-profile-filament::webauthn-waiting-indicator
        x-show="processing"
        style="display: none;"
        x-cloak
        wire:ignore
        :message="__('profile-filament::auth/multi-factor/webauthn/provider.messages.waiting-for-input')"
    />

    <div x-show="hasErrors && ! processing">
        <template x-if="hasErrors && ! validationError">
            <p class="text-danger-500 text-center text-pretty mb-1.5" role="alert">
                {{ __('profile-filament::auth/multi-factor/webauthn/passkeys.login.messages.failed') }}
            </p>
        </template>

        <template x-if="hasErrors && validationError">
            <p class="text-danger-500 text-center text-pretty mb-1.5" role="alert" x-text="validationError">
            </p>
        </template>
    </div>

    <div x-on:click="await authenticate">
        @if ($slot->isEmpty())
            <x-filament::link
                tag="button"
                class="w-full"
                color="neutral"
                x-show="! processing"
            >
                {{ __('profile-filament::auth/multi-factor/webauthn/passkeys.login.actions.authenticate.label') }}
            </x-filament::link>
        @else
            {{ $slot }}
        @endif
    </div>
</div>

