<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Actions\Mfa;

use Filament\Actions\Action;
use Livewire\Component;
use Rawilk\ProfileFilament\Filament\Actions\Sudo\Concerns\RequiresSudo;

class AddWebauthnKeyAction extends Action
{
    use RequiresSudo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->color('gray');

        $this->label(__('profile-filament::pages/security.mfa.webauthn.actions.register.trigger'));

        $this->mountUsing(function (Component $livewire) {
            $this->mountSudoAction($livewire);
        });

        $this->registerModalActions([
            $this->getSudoChallengeAction(),
        ]);
    }
}
