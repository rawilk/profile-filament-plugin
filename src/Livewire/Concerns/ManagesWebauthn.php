<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Livewire\Concerns;

use Filament\Actions\Action;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Rawilk\ProfileFilament\Enums\Livewire\MfaEvent;
use Rawilk\ProfileFilament\Filament\Actions\Mfa\ToggleWebauthnAction;

/**
 * @mixin \Rawilk\ProfileFilament\Livewire\MfaOverview
 *
 * @property-read bool $canUseWebauthn
 * @property-read Collection<int, \Rawilk\ProfileFilament\Models\WebauthnKey> $webauthnKeys
 */
trait ManagesWebauthn
{
    #[Locked]
    public bool $showWebauthn = false;

    #[Computed]
    public function canUseWebauthn(): bool
    {
        return $this->panelFeatures->hasWebauthn();
    }

    #[Computed]
    public function webauthnKeys(): Collection
    {
        if (! $this->canUseWebauthn) {
            return collect();
        }

        return $this->user->nonPasskeyWebauthnKeys ?? collect();
    }

    public function toggleWebauthnAction(): Action
    {
        return ToggleWebauthnAction::make('toggleWebauthn')
            ->label(function (): string {
                if (! $this->showWebauthn && $this->webauthnKeys->isEmpty()) {
                    return __('profile-filament::pages/security.mfa.webauthn.add_button');
                }

                return $this->showWebauthn
                    ? __('profile-filament::pages/security.mfa.webauthn.hide_button')
                    : __('profile-filament::pages/security.mfa.webauthn.show_button');
            })
            ->beforeSudoModeCheck(function (): bool {
                // Don't bother checking/prompting for sudo mode if these conditions are not met.
                if ($this->showWebauthn) {
                    return false;
                }

                return $this->webauthnKeys->isEmpty();
            })
            ->action(function () {
                $this->showWebauthn = ! $this->showWebauthn;

                $this->dispatch(MfaEvent::ToggleWebauthnKeys->value, show: $this->showWebauthn);
            });
    }
}
