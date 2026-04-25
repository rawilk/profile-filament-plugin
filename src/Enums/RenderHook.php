<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Enums;

enum RenderHook: string
{
    case MultiFactorChallengeBefore = 'profile-filament::multi-factor-challenge.before';
    case MultiFactorChallengeAfter = 'profile-filament::multi-factor-challenge.after';
}
