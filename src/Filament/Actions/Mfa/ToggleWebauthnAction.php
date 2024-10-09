<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Actions\Mfa;

use Filament\Actions\Action;
use Filament\Support\Enums\ActionSize;
use Livewire\Component;
use Rawilk\ProfileFilament\Filament\Actions\Sudo\Concerns\RequiresSudo;

class ToggleWebauthnAction extends Action
{
    use RequiresSudo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->color('gray');

        $this->size(ActionSize::Small);

        $this->mountUsing(function (Component $livewire) {
            $this->mountSudoAction($livewire);
        });

        $this->registerModalActions([
            $this->getSudoChallengeAction(),
        ]);
    }

    public static function getDefaultName(): ?string
    {
        return 'toggleWebauthn';
    }
}
