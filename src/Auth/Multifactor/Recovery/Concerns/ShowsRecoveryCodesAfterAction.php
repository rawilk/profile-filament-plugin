<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Recovery\Concerns;

use Filament\Actions\Contracts\HasActions;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Crypt;
use Rawilk\ProfileFilament\Auth\Multifactor\Recovery\Actions\ShowRecoveryCodesAction;
use Rawilk\ProfileFilament\Auth\Multifactor\Recovery\Contracts\HasMultiFactorAuthenticationRecovery;
use Rawilk\ProfileFilament\Auth\Multifactor\Recovery\Enums\RecoveryCodeSession;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

/**
 * @mixin \Filament\Actions\Action
 */
trait ShowsRecoveryCodesAfterAction
{
    protected function registerShowsRecoveryCodes(): void
    {
        $this->registerModalActions([
            ShowRecoveryCodesAction::make(actionName: 'showRecoveryCodes')
                ->modalHeading(__('profile-filament::auth/multi-factor/recovery/actions/show-recovery-codes.modal.heading')),
        ]);
    }

    protected function setUpRecoveryCodesIfNeeded(HasActions $livewire, Authenticatable $user): void
    {
        if (! $this->needsToSetUpRecoveryCodes($user)) {
            return;
        }

        /** @var ProfileFilamentPlugin $plugin */
        $plugin = filament(ProfileFilamentPlugin::PLUGIN_ID);
        $provider = $plugin->getMultiFactorRecoveryProvider();

        $recoveryCodes = $provider->generateRecoveryCodes();
        $provider->saveRecoveryCodes($user, $recoveryCodes);

        // Work-around to help prevent action modals that hide themselves once the user has enabled
        // a mfa method.
        RecoveryCodeSession::SettingUp->put(true);

        $livewire->mountAction('showRecoveryCodes', arguments: [
            'encrypted' => Crypt::encrypt([
                'recoveryCodes' => $recoveryCodes,
            ]),
        ]);
    }

    protected function needsToSetUpRecoveryCodes(Authenticatable $user): bool
    {
        /** @var ProfileFilamentPlugin $plugin */
        $plugin = filament(ProfileFilamentPlugin::PLUGIN_ID);

        if (! $plugin->isMultiFactorRecoverable()) {
            return false;
        }

        if (! ($user instanceof HasMultiFactorAuthenticationRecovery)) {
            return false;
        }

        return $plugin->getMultiFactorRecoveryProvider()->needsToBeSetup($user);
    }
}
