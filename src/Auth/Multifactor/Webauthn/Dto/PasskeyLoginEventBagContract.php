<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Dto;

use Rawilk\ProfileFilament\Auth\Multifactor\Filament\Dto\MultiFactorEventBagContract;
use Rawilk\ProfileFilament\Models\WebauthnKey;

interface PasskeyLoginEventBagContract extends MultiFactorEventBagContract
{
    public function getPasskey(): ?WebauthnKey;

    public function setPasskey(?WebauthnKey $passkey): static;
}
