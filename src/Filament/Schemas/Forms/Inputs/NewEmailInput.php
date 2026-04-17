<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Schemas\Forms\Inputs;

use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;

class NewEmailInput extends TextInput
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('profile-filament::pages/settings.email.actions.edit.email_label'));

        $this->placeholder(__('profile-filament::pages/settings.email.actions.edit.email_placeholder', ['host' => request()?->getHost()]));

        $this->belowContent(
            fn (): ?string => Filament::hasEmailChangeVerification()
                ? __('profile-filament::pages/settings.email.actions.edit.email_help')
                : null
        );

        $this->autocomplete('new-email');

        $this->required();

        $this->email();

        $this->unique(
            table: fn () => app(config('auth.providers.users.model'))->getTable(),
            column: 'email',
            ignoreRecord: true,
        );
    }
}
