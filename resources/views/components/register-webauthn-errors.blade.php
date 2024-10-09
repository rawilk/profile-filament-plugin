@props([
    'errorMessage' => __('profile-filament::pages/security.mfa.webauthn.actions.register.register_fail'),
    'button' => false,
])

<div
    {{
        $attributes
            ->class([
                'pf-webauthn-register-errors flex flex-col items-center'
            ])
            ->style([
                'display: none;',
            ])
            ->merge([
                'x-cloak' => '',
                'x-show' => 'error && ! processing',
                'wire:ignore' => '',
            ])
    }}
>
    <div class="flex items-center gap-x-2 text-danger-600 dark:text-danger-400" wire:ignore>
        <div class="shrink-0">
            <x-filament::icon
                alias="profile-filament::webauthn-error"
                icon="heroicon-o-exclamation-triangle"
                class="h-4 w-4"
            />
        </div>

        <div class="text-sm flex-1">
            {{ $errorMessage }}
        </div>
    </div>

    @if ($button)
        <div class="mt-3">
            {{ $button }}
        </div>
    @endif
</div>
