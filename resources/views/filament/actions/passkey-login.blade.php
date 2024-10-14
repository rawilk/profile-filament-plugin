<x-profile-filament::webauthn-script
    mode="authenticate"
    x-data="authenticateWebauthn({
        publicKeyUrl: {{ Js::from($passkeyOptionsUrl()) }},
        loginUsing: function (answer) {
            $wire.mountAction({{ Js::from($getName()) }}, {
                assertion: answer,
            });
        }
    })"
    wire:ignore.self
>
    <x-profile-filament::webauthn-waiting-indicator
        x-show="processing"
        style="display: none;"
        x-cloak
        wire:ignore
        :message="__('profile-filament::pages/mfa.webauthn.waiting')"
    />

    <div x-show="! processing">
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
        >
            {{ $getLabel() }}
        </x-filament-actions::action>
    </div>
</x-profile-filament::webauthn-script>
