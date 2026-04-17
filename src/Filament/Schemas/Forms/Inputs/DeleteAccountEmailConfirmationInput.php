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

        $this->label(__('profile-filament::pages/settings.delete_account.actions.delete.email_label', [
            'email' => Filament::auth()->user()->email,
        ]));

        $this->email();

        $this->required();

        $this->rules([
            fn (): Closure => function (string $attribute, $value, Closure $fail) {
                if (Str::lower($value) !== Str::lower(Filament::auth()->user()->email)) {
                    $fail(__('profile-filament::pages/settings.delete_account.actions.delete.incorrect_email'));
                }
            },
        ]);
    }
}
