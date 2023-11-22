<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Testing\Support;

use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\TrustPath\EmptyTrustPath;

/**
 * Fake webauthn data to help facilitate automated webauthn testing.
 * All keys in this class assume you are using the domain `acme.test`.
 *
 * DO NOT USE THIS OUTSIDE OF TESTS!
 */
class FakeWebauthn
{
    public const CREDENTIAL_ID = 'AFpSEqxSXl--14Du3RUPCA';

    public const ASSERTION_CHALLENGE = 'oDpfgU0SqPyEs5Kb7SVjV3tBMPEYj23PrYBPf6csrzE';

    public const ATTESTATION_CHALLENGE = 'bgbN-Wj9pcKnqDEXA09AyGsYKA2ehFILw1RZABvAuMg';

    public static function rawAssertionChallenge(): string
    {
        return Base64UrlSafe::decodeNoPadding(static::ASSERTION_CHALLENGE);
    }

    public static function rawAttestationChallenge(): string
    {
        return Base64UrlSafe::decodeNoPadding(static::ATTESTATION_CHALLENGE);
    }

    public static function rawCredentialId(): string
    {
        return Base64UrlSafe::decodeNoPadding(static::CREDENTIAL_ID);
    }

    public static function attestationResponse(): array
    {
        return [
            'id' => static::CREDENTIAL_ID,
            'rawId' => static::CREDENTIAL_ID,
            'response' => [
                'attestationObject' => 'o2NmbXRkbm9uZWdhdHRTdG10oGhhdXRoRGF0YViUhrMYOY_f143oylJb_T6r0SA83i_NjHIZAGsvtDBBulFdAAAAALraVWanqkAfvZZFYZpVEg0AEABaUhKsUl5fvteA7t0VDwilAQIDJiABIVggKyYRi_qPItmFAJm0HkaGPTlL1IqM-35BEt_ATs8XVzoiWCC2bKioMwsHZWWrIrue9rW0XnXdEKp9PSQMaPTD8BVHmw',
                'clientDataJSON' => 'eyJ0eXBlIjoid2ViYXV0aG4uY3JlYXRlIiwiY2hhbGxlbmdlIjoiYmdiTi1XajlwY0tucURFWEEwOUF5R3NZS0EyZWhGSUx3MVJaQUJ2QXVNZyIsIm9yaWdpbiI6Imh0dHBzOi8vYWNtZS50ZXN0In0',
                'transports' => [
                    'internal',
                    'hybrid',
                ],
                'publicKeyAlgorithm' => -7,
                'publicKey' => 'MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAEKyYRi_qPItmFAJm0HkaGPTlL1IqM-35BEt_ATs8XVzq2bKioMwsHZWWrIrue9rW0XnXdEKp9PSQMaPTD8BVHmw',
                'authenticatorData' => 'hrMYOY_f143oylJb_T6r0SA83i_NjHIZAGsvtDBBulFdAAAAALraVWanqkAfvZZFYZpVEg0AEABaUhKsUl5fvteA7t0VDwilAQIDJiABIVggKyYRi_qPItmFAJm0HkaGPTlL1IqM-35BEt_ATs8XVzoiWCC2bKioMwsHZWWrIrue9rW0XnXdEKp9PSQMaPTD8BVHmw',
            ],
            'type' => 'public-key',
            'clientExtensionResults' => [],
            'authenticatorAttachment' => 'platform',
        ];
    }

    public static function assertionResponse(): array
    {
        return [
            'id' => static::CREDENTIAL_ID,
            'type' => 'public-key',
            'rawId' => static::CREDENTIAL_ID,
            'response' => [
                'clientDataJSON' => 'eyJ0eXBlIjoid2ViYXV0aG4uZ2V0IiwiY2hhbGxlbmdlIjoib0RwZmdVMFNxUHlFczVLYjdTVmpWM3RCTVBFWWoyM1ByWUJQZjZjc3J6RSIsIm9yaWdpbiI6Imh0dHBzOi8vYWNtZS50ZXN0In0',
                'authenticatorData' => 'hrMYOY_f143oylJb_T6r0SA83i_NjHIZAGsvtDBBulEdAAAAAA',
                'signature' => 'MEUCIQCqydrFfu0oGDqBZBeczACPxjeFg04fxzB5al7B4kDEQwIgIZSFRFn3KIPL0UyujbN-fdOzEh1pt6D2CuyULS3uOI8',
                'userHandle' => 'MQ',
            ],
        ];
    }

    public static function publicKey(): array
    {
        return [
            'publicKeyCredentialId' => static::CREDENTIAL_ID,
            'type' => 'public-key',
            'transports' => [
                'internal',
                'hybrid',
            ],
            'attestationType' => 'none',
            'trustPath' => [
                'type' => EmptyTrustPath::class,
            ],
            'aaguid' => '00000000-0000-0000-0000-000000000000',
            'credentialPublicKey' => 'pQECAyYgASFYICsmEYv6jyLZhQCZtB5Ghj05S9SKjPt-QRLfwE7PF1c6IlggtmyoqDMLB2VlqyK7nva1tF513RCqfT0kDGj0w_AVR5s',
            'userHandle' => 'MQ', // 1
            'counter' => 0,
            'otherUI' => null,
        ];
    }
}
