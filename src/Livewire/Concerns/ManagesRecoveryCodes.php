<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Livewire\Concerns;

use Filament\Actions\Action;
use Livewire\Attributes\Locked;
use Rawilk\ProfileFilament\Events\RecoveryCodesViewed;
use Rawilk\ProfileFilament\Filament\Actions\Mfa\ToggleRecoveryCodesAction;

trait ManagesRecoveryCodes
{
    #[Locked]
    public bool $showRecoveryCodes = false;

    #[Locked]
    public bool $showRecoveryInModal = false;

    public function toggleRecoveryCodesAction(): Action
    {
        return ToggleRecoveryCodesAction::make('toggleRecoveryCodes')
            ->label(fn (): string => $this->showRecoveryCodes ? __('profile-filament::pages/security.mfa.recovery_codes.hide_button') : __('profile-filament::pages/security.mfa.recovery_codes.show_button'))
            ->disabled(fn (): bool => ! $this->hasMfaEnabled)
            ->beforeSudoModeCheck(function (): bool {
                // Hiding the recovery codes shouldn't require re-authentication.
                if ($this->showRecoveryCodes) {
                    return false;
                }

                return true;
            })
            ->action(function () {
                $this->showRecoveryCodes = ! $this->showRecoveryCodes;

                if ($this->showRecoveryCodes) {
                    RecoveryCodesViewed::dispatch($this->user);
                }
            });
    }
}
