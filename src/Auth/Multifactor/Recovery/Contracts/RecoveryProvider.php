<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Recovery\Contracts;

use Filament\Actions\Action;
use Filament\Schemas\Components\Component;
use Illuminate\Contracts\Auth\Authenticatable;

interface RecoveryProvider
{
    public function isEnabled(HasMultiFactorAuthenticationRecovery $user): bool;

    /**
     * Lets our actions know if recovery codes need to be created for a user.
     */
    public function needsToBeSetup(HasMultiFactorAuthenticationRecovery $user): bool;

    public function generateRecoveryCodes(): array;

    public function saveRecoveryCodes(HasMultiFactorAuthenticationRecovery $user, ?array $codes): void;

    /**
     * @return array<Component|Action>
     */
    public function getManagementSchemaComponents(): array;

    /**
     * @return array<Component|Action|\Filament\Actions\ActionGroup>
     */
    public function getChallengeFormComponents(Authenticatable $user): array;

    public function getChallengeSubmitLabel(): ?string;

    public function getChangeToProviderActionLabel(Authenticatable $user): ?string;
}
