<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Email\Concerns;

/**
 * @property bool $has_email_authentication
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait InteractsWithEmailAuthentication
{
    public function hasEmailAuthentication(): bool
    {
        return (bool) $this->has_email_authentication;
    }

    public function toggleEmailAuthentication(bool $condition): void
    {
        if ($condition === $this->has_email_authentication) {
            return;
        }

        $this->has_email_authentication = $condition;
        $this->save();
    }

    protected function initializeInteractsWithEmailAuthentication(): void
    {
        $this->mergeCasts([
            'has_email_authentication' => 'boolean',
        ]);
    }
}
