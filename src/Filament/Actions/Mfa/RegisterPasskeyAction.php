<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Actions\Mfa;

use Closure;
use Filament\Actions\Action;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\View;
use Livewire\Component;
use Rawilk\ProfileFilament\Filament\Actions\Sudo\Concerns\RequiresSudo;
use Rawilk\ProfileFilament\Livewire\TwoFactorAuthentication\WebauthnKey;

class RegisterPasskeyAction extends Action
{
    use RequiresSudo;

    protected null|Closure|WebauthnKey|Model $webauthnKeyToUpgrade = null;

    protected ?Closure $preCheck = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requiresConfirmation();

        $this->color('primary');

        $this->label(__('profile-filament::pages/security.passkeys.actions.add.trigger'));

        $this->modalIcon('pf-passkey');

        $this->modalIconColor('primary');

        $this->modalDescription(null);

        $this->modalSubmitAction(false);

        $this->modalCancelAction(false);

        $this->modalWidth(MaxWidth::Large);

        $this->modalHeading(
            fn () => $this->getUpgradable()
                ? __('profile-filament::pages/security.passkeys.actions.upgrade.modal_title')
                : __('profile-filament::pages/security.passkeys.actions.add.modal_title')
        );

        $this->modalContent(
            fn (): View => view('profile-filament::components.register-passkey-modal', [
                'upgrading' => $this->getUpgradable(),
            ])
        );

        $this->before(function (Component $livewire) {
            $this->ensureSudoIsActive($livewire);
        });

        $this->mountUsing(function (Component $livewire, array $arguments) {
            if (is_callable($this->preCheck)) {
                $this->evaluate($this->preCheck);
            }

            $this->mountSudoAction($livewire);
        });

        $this->registerModalActions([
            $this->getSudoChallengeAction(),
        ]);
    }

    public function upgrading(null|Closure|WebauthnKey|Model $webauthnKey): static
    {
        $this->webauthnKeyToUpgrade = $webauthnKey;

        return $this;
    }

    public function preCheck(?Closure $callback): static
    {
        $this->preCheck = $callback;

        return $this;
    }

    public function getUpgradable(): null|WebauthnKey|Model
    {
        return $this->evaluate($this->webauthnKeyToUpgrade);
    }
}
