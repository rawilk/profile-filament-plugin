<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Livewire\TwoFactorAuthentication;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Facades\FilamentIcon;
use Livewire\Attributes\Locked;
use Rawilk\ProfileFilament\Concerns\CopiesRecoveryCodes;
use Rawilk\ProfileFilament\Concerns\Sudo\UsesSudoChallengeAction;
use Rawilk\ProfileFilament\Contracts\TwoFactor\GenerateNewRecoveryCodesAction;
use Rawilk\ProfileFilament\Livewire\ProfileComponent;

/**
 * @property-read array $recoveryCodes
 */
class RecoveryCodes extends ProfileComponent
{
    use CopiesRecoveryCodes;
    use UsesSudoChallengeAction;

    #[Locked]
    public bool $regenerated = false;

    public function generateAction(): Action
    {
        return Action::make('generate')
            ->color('gray')
            ->label(__('profile-filament::pages/security.mfa.recovery_codes.actions.generate.button'))
            ->requiresConfirmation()
            ->modalIcon(FilamentIcon::resolve('mfa::recovery-codes') ?? 'heroicon-o-key')
            ->modalIconColor('primary')
            ->action(function (GenerateNewRecoveryCodesAction $generator) {
                // Re-check for sudo mode in case the user waited too long to confirm.
                $this->ensureSudoIsActive(returnAction: 'generate');

                $this->regenerated = true;

                $generator(filament()->auth()->user());

                Notification::make()
                    ->title(__('profile-filament::pages/security.mfa.recovery_codes.actions.generate.success_title'))
                    ->body(__('profile-filament::pages/security.mfa.recovery_codes.actions.generate.success_message'))
                    ->success()
                    ->persistent()
                    ->send();
            })
            ->mountUsing(function () {
                $this->ensureSudoIsActive(returnAction: 'generate');
            });
    }

    protected function view(): string
    {
        return 'profile-filament::livewire.two-factor-authentication.recovery-codes';
    }
}
