<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Support\SudoChallengeProviders;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Support\Collection;

readonly class Factory
{
    public function __invoke(?User $user = null): Collection
    {
        return collect(config('profile-filament.sudo.challenge_providers', []))
            ->filter(fn (string $challengeMode) => $challengeMode::allowedFor($user));
    }
}
