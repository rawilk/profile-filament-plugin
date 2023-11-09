<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Enums\Session;

enum MfaSession: string
{
    case Confirmed = 'mfa.confirmed';
    case User = 'login.id';
    case Remember = 'login.remember';

    // Webauthn
    case AttestationPublicKey = 'webauthn:attestation:public_key';
    case AssertionPublicKey = 'webauthn:assertion:public_key';
    case PasskeyAttestationPk = 'passkey:attestation:public_key';
    case PasskeyAssertionPk = 'passkey:assertion:public_key';
}
