<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Schemas\Forms\Inputs;

use Closure;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Str;

class DeleteAccountEmailConfirmationInput extends TextInput
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('profile-filament::pages/settings/delete-account/actions/delete.modal.form.email.label', [
            'email' => Filament::auth()->user()->email,
        ]));

        $this->validationAttribute(__('profile-filament::pages/settings/delete-account/actions/delete.modal.form.email.validation-attribute'));

        $this->email();

        $this->required();

        $this->rules([
            fn (): Closure => function (string $attribute, $value, Closure $fail) {
                if (
                    ! hash_equals(Str::lower(Filament::auth()->user()->email), Str::lower($value))
                ) {
                    $fail(__('profile-filament::pages/settings/delete-account/actions/delete.modal.form.email.messages.incorrect'));
                }
            },
        ]);
    }
}
