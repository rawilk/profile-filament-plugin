<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Contracts;

use Filament\Actions\Action;
use Filament\Schemas\Components\Component;
use Illuminate\Contracts\Auth\Authenticatable;

interface MultiFactorAuthenticationProvider
{
    public function isEnabled(Authenticatable $user): bool;

    public function getId(): string;

    public function getSelectLabel(): string;

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
