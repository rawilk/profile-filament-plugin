<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Schemas\Forms\Inputs;

use Filament\Forms\Components\TextInput;

class ProfileNameInput extends TextInput
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('profile-filament::pages/profile/actions/edit.modal.form.name.label'));

        $this->validationAttribute(__('profile-filament::pages/profile/actions/edit.modal.form.name.validation-attribute'));

        $this->required();

        $this->maxLength(255);
    }

    public static function getDefaultName(): ?string
    {
        return 'name';
    }
}
