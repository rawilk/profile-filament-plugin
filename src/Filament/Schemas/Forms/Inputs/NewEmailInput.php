<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Schemas\Forms\Inputs;

use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Rawilk\ProfileFilament\Facades\ProfileFilament;
use Rawilk\ProfileFilament\Support\Config;

class NewEmailInput extends TextInput
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('profile-filament::pages/settings/email/actions/edit.modal.form.email.label'));

        $this->validationAttribute(__('profile-filament::pages/settings/email/actions/edit.modal.form.email.validation-attribute'));

        $this->placeholder(__('profile-filament::pages/settings/email/actions/edit.modal.form.email.placeholder', ['host' => request()?->getHost()]));

        $this->belowContent(
            fn (): ?string => Filament::hasEmailChangeVerification()
                ? __('profile-filament::pages/settings/email/actions/edit.modal.form.email.help')
                : null
        );

        $this->autocomplete('new-email');

        $this->required();

        $this->email();

        $this->when(
            fn (): bool => ProfileFilament::plugin()->isEnforcingUniqueEmail(),
            fn (TextInput $component) => $component->unique(
                table: fn () => app(Config::getAuthenticatableModel())->getTable(),
                column: 'email',
                ignoreRecord: true,
            ),
        );
    }
}
