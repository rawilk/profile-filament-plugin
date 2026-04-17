<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Plugin\Concerns;

use Closure;
use Rawilk\ProfileFilament\Filament\Pages\EmailVerificationPrompt;

trait HasEmailVerification
{
    protected string|Closure|array|null $emailVerificationPromptAction = EmailVerificationPrompt::class;

    /**
     * @param  string|Closure|array<class-string, string>|null  $promptAction
     */
    public function emailVerification(string|Closure|array|null $promptAction = EmailVerificationPrompt::class): static
    {
        $this->emailVerificationPromptAction = $promptAction;

        return $this;
    }

    public function getEmailVerificationPromptRouteAction(): string|Closure|array|null
    {
        return $this->emailVerificationPromptAction;
    }
}
