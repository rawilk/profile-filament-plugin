<x-profile-filament::multi-factor.webauthn-register>
    if (securityKey) {
        @this.call('register', { securityKey: JSON.stringify(securityKey) });
    }
</x-profile-filament::multi-factor.webauthn-register>
