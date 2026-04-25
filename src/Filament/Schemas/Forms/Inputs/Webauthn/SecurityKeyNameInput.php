<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Schemas\Forms\Inputs\Webauthn;

use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Illuminate\Validation\Rules\Unique;
use Rawilk\ProfileFilament\Support\Config;

class SecurityKeyNameInput extends TextInput
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('profile-filament::auth/multi-factor/webauthn/actions/set-up.modal.form.name.label'));

        $this->placeholder(__('profile-filament::auth/multi-factor/webauthn/actions/set-up.modal.form.name.placeholder'));

        $this->validationAttribute(__('profile-filament::auth/multi-factor/webauthn/actions/set-up.modal.form.name.validation-attribute'));

        $this->required();

        $this->maxLength(255);

        $this->autocomplete('off');

        $this->unique(
            table: Config::getTableName('webauthn_key'),
            ignoreRecord: true,
            modifyRuleUsing: function (Unique $rule): void {
                $rule->where('user_id', Filament::auth()->id());
            },
        );
    }
}
