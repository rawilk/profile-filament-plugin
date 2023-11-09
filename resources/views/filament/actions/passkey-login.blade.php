<div
    x-ignore
    ax-load="visible"
    ax-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('webauthnForm', package: 'rawilk/profile-filament-plugin') }}"
    x-data="webauthnForm({
        mode: 'login',
        wireId: '{{ $this->getId() }}',
        loginPublicKeyUrl: '{{ URL::signedRoute('profile-filament::webauthn.passkey_assertion_pk', ['s' => Hash::make(session()->getId())]) }}',
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
