<x-filament-panels::page.simple class="fi-mfa-challenge">
    <x-filament-panels::form wire:submit="authenticate">
        <x-profile-filament::mfa.form
            :alternate-challenge-options="$this->alternateChallengeOptions"
            :challenge-mode="$this->challengeMode"
            :error="$this->error"
            :form="$this->form"
            :user="$this->user"
        />
    </x-filament-panels::form>
</x-filament-panels::page.simple>
