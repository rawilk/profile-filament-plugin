<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Concerns\Sudo;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Support\Collection;
use Rawilk\ProfileFilament\Enums\Livewire\SudoChallengeMode;
use Rawilk\ProfileFilament\Facades\Mfa;
use Rawilk\ProfileFilament\Facades\Sudo;

trait ChallengesSudoMode
{
    protected function sudoModeIsActive(): bool
    {
        return Sudo::isActive();
    }

    protected function mapAlternateChallengeOptions(array $challengeOptions, string $currentChallengeMode, User $user): Collection
    {
        return collect($challengeOptions)
            ->filter(fn (string $option) => $option !== $currentChallengeMode)
            ->map(function (string $option) use ($user) {
                $mode = SudoChallengeMode::tryFrom($option) ?? $option;

                return [
                    'key' => $option,
                    'label' => is_string($mode) ? $mode : $mode->linkLabel($user),
                ];
            });
    }

    protected function sudoChallengeOptionsFor(User $user): array
    {
        $options = [];

        if (Mfa::canUseAuthenticatorAppsForChallenge($user)) {
            $options[] = SudoChallengeMode::App->value;
        }

        // Passkeys or security key
        if (Mfa::canUseWebauthnForChallenge($user)) {
            $options[] = SudoChallengeMode::Webauthn->value;
        }

        if (filled($user->getAuthPassword())) {
            $options[] = SudoChallengeMode::Password->value;
        }

        return $options;
    }
}
