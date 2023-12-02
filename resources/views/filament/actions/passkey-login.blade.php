<x-profile-filament::webauthn-script>
    <div
        x-data="webauthnForm({
            mode: 'login',
            wireId: '{{ $this->getId() }}',
            {{-- adding time variable to url so a unique signature is always generated --}}
            loginPublicKeyUrl: '{{ URL::signedRoute('profile-filament::webauthn.passkey_assertion_pk', ['t' => now()->unix()]) }}',
            loginUsing: function (assertion) {
                return @this.mountAction('{{ $getName() }}', { assertion });
            },
        })"
        x-on:click.prevent.stop="submit"
    >
        <x-profile-filament::webauthn-waiting-indicator
            :message="__('profile-filament::pages/mfa.webauthn.waiting')"
            class="text-sm"
            style="display: none;"
            x-cloak
            x-show="processing"
        />

        <x-filament-actions::action
            :action="$action"
            :badge="$getBadge()"
            :badge-color="$getBadgeColor()"
            dynamic-component="filament::button"
            :icon-position="$getIconPosition()"
            :labeled-from="$getLabeledFromBreakpoint()"
            :outlined="$isOutlined()"
            :size="$getSize()"
            class="fi-ac-btn-action"
            x-show="! processing"
        >
            {{ $getLabel() }}
        </x-filament-actions::action>
    </div>
</x-profile-filament::webauthn-script>
