<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Recovery\Actions;

use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Crypt;
use LogicException;
use Rawilk\ProfileFilament\Auth\Multifactor\Recovery\RecoveryCodeProvider;
use Rawilk\ProfileFilament\Auth\Sudo\Actions\Concerns\RequiresSudoChallenge;
use Rawilk\ProfileFilament\Events\RecoveryCodesRegenerated;

class RegenerateRecoveryCodesAction extends Action
{
    use RequiresSudoChallenge;

    protected ?RecoveryCodeProvider $recoveryProvider = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerSudoChallenge();

        $this->color('primary');

        $this->size(Size::Small);

        $this->closeModalByClickingAway(false);
        $this->closeModalByEscaping(false);

        $this->label(__('profile-filament::auth/multi-factor/recovery/actions/regenerate-recovery-codes.label'));

        $this->requiresConfirmation();

        $this->modalHeading(__('profile-filament::auth/multi-factor/recovery/actions/regenerate-recovery-codes.modal.title'));
        $this->modalDescription(str(__('profile-filament::auth/multi-factor/recovery/actions/regenerate-recovery-codes.modal.description'))->inlineMarkdown()->toHtmlString());
        $this->modalSubmitActionLabel(__('profile-filament::auth/multi-factor/recovery/actions/regenerate-recovery-codes.modal.actions.confirm.label'));
        $this->modalIcon(Heroicon::ArrowPath);

        $this->rateLimit(5);

        $this->cancelParentActions();

        $this->action(function (HasActions $livewire) {
            if ($this->shouldChallengeForSudo()) {
                $this->cancel();
            }

            /** @var \Rawilk\ProfileFilament\Auth\Multifactor\Recovery\Contracts\HasMultiFactorAuthenticationRecovery|\Illuminate\Contracts\Auth\Authenticatable $user */
            $user = Filament::auth()->user();

            $recoveryProvider = $this->getProvider();
            $recoveryCodes = $recoveryProvider->generateRecoveryCodes();

            $recoveryProvider->saveRecoveryCodes($user, $recoveryCodes);

            RecoveryCodesRegenerated::dispatch($user);

            $livewire->mountAction('showNewRecoveryCodes', arguments: [
                'encrypted' => Crypt::encrypt([
                    'recoveryCodes' => $recoveryCodes,
                ]),
            ]);

            Notification::make()
                ->title(__('profile-filament::auth/multi-factor/recovery/actions/regenerate-recovery-codes.notifications.regenerated.title'))
                ->success()
                ->icon(Heroicon::OutlinedArrowPath)
                ->send();
        });

        $this->registerModalActions([
            ShowRecoveryCodesAction::make(actionName: 'showNewRecoveryCodes')
                ->modalHeading(__('profile-filament::auth/multi-factor/recovery/actions/show-new-recovery-codes.modal.heading')),
        ]);
    }

    public static function getDefaultName(): ?string
    {
        return 'regenerateRecoveryCodes';
    }

    public function provider(RecoveryCodeProvider $provider): static
    {
        $this->recoveryProvider = $provider;

        return $this;
    }

    public function getProvider(): RecoveryCodeProvider
    {
        throw_unless(
            $this->recoveryProvider instanceof RecoveryCodeProvider,
            new LogicException('Recovery provider must be an instance of [' . RecoveryCodeProvider::class . '] to use the regenerate recovery codes action'),
        );

        return $this->recoveryProvider;
    }
}
