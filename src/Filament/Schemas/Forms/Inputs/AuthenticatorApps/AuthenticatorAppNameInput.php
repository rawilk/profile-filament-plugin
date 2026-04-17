<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Schemas\Forms\Inputs\AuthenticatorApps;

use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Illuminate\Validation\Rules\Unique;

class AuthenticatorAppNameInput extends TextInput
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('profile-filament::auth/multi-factor/app/actions/set-up.modal.form.name.label'));

        $this->placeholder(__('profile-filament::auth/multi-factor/app/actions/set-up.modal.form.name.placeholder'));

        $this->belowContent(__('profile-filament::auth/multi-factor/app/actions/set-up.modal.form.name.help'));

        $this->required();

        $this->autocomplete('off');

        $this->maxLength(255);

        $this->unique(
            table: config('profile-filament.table_names.authenticator_app'),
            ignoreRecord: true,
            modifyRuleUsing: function (Unique $rule): void {
                $rule->where('user_id', Filament::auth()->id());
            },
        );
    }
}
