<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Plugin\Concerns;

use Closure;

trait UpdatesUserPassword
{
    protected bool|Closure $currentPasswordRequired = true;

    protected bool|Closure $passwordConfirmationRequired = true;

    protected bool|Closure $shouldShowPasswordResetLinkInUpdatePasswordForm = true;

    public function requireCurrentPassword(bool|Closure $condition = true): static
    {
        $this->currentPasswordRequired = $condition;

        return $this;
    }

    public function requirePasswordConfirmation(bool|Closure $condition = true): static
    {
        $this->passwordConfirmationRequired = $condition;

        return $this;
    }

    public function showPasswordResetLinkInUpdatePasswordForm(bool|Closure $condition = true): static
    {
        $this->shouldShowPasswordResetLinkInUpdatePasswordForm = $condition;

        return $this;
    }

    public function isCurrentPasswordRequired(): bool
    {
        return (bool) $this->evaluate($this->currentPasswordRequired);
    }

    public function isPasswordConfirmationRequired(): bool
    {
        return (bool) $this->evaluate($this->passwordConfirmationRequired);
    }

    public function shouldShowPasswordResetLinkInUpdatePasswordForm(): bool
    {
        if (! $this->isCurrentPasswordRequired()) {
            return false;
        }

        return (bool) $this->evaluate($this->shouldShowPasswordResetLinkInUpdatePasswordForm);
    }
}
