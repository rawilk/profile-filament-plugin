<div
    class="text-sm text-gray-500 dark:text-gray-400 [&_a]:text-custom-600 [&_a]:fi-link [&_a:focus]:underline [&_a:hover]:underline dark:[&_a]:text-custom-400"
    @style([
        \Filament\Support\get_color_css_variables('primary', [300, 400, 500, 600]),
    ])
>
    @include('profile-filament::livewire.partials.authenticator-app-instructions')

    <x-filament-panels::form
        class="mt-3"
        :wire:key="$this->getId() . '.forms.data'"
        wire:submit="confirm"
    >
        {{ $this->form }}

        @if ($this->showCodeError)
            <div class="pr-2">
                <x-profile-filament::alert>
                    <p>{{ __('profile-filament::pages/security.mfa.app.code_verification_fail') }}</p>
                </x-profile-filament::alert>
            </div>
        @elseif ($codeValid)
            <div class="pr-2" id="2fa-code-verify-success">
                <x-profile-filament::alert color="success">
                    <p>{{ __('profile-filament::pages/security.mfa.app.code_verification_pass') }}</p>
                </x-profile-filament::alert>
            </div>
        @endif

        <div class="flex gap-x-2">
            {{ $this->submitAction }}

            {{ $this->cancelAction }}
        </div>
    </x-filament-panels::form>
</div>
