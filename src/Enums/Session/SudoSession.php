<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Enums\Session;

enum SudoSession: string
{
    case ConfirmedAt = 'sudo.confirmed_at';
    case WebauthnAssertionPk = 'sudo.webauthn:assertion:public_key';
}
