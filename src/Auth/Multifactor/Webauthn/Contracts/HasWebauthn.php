<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Contracts;

use Illuminate\Database\Eloquent\Relations\HasMany;

interface HasWebauthn
{
    public function securityKeys(): HasMany;

    public function getPasskeyName(): string;

    public function getPasskeyId(): string;

    public function getPasskeyDisplayName(): string;
}
