@props([
    'upgrading' => null,
])

<div>
    @livewire(\Rawilk\ProfileFilament\Livewire\PasskeyRegistrationForm::class, [
        'upgrading' => $upgrading,
    ])
</div>
