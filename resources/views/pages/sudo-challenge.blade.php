<x-filament-panels::page.simple class="fi-sudo-challenge">
    <x-filament-panels::form
        wire:submit="confirm"
        :wire:key="$this->getId() . '.forms.data'"
        class="mt-3"
    >
        <x-profile-filament::sudo.form
            :user="$this->user"
            :user-handle="$this->userHandle()"
            :challenge-mode="$this->challengeMode"
            :alternate-challenge-options="$this->alternateChallengeOptions"
            :error="$this->error"
            :form="$this->form"
        />
    </x-filament-panels::form>
</x-filament-panels::page.simple>
