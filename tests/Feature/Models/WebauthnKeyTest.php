<?php

declare(strict_types=1);

use ParagonIE\ConstantTime\Base64UrlSafe;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Webauthn\PublicKeyCredentialSource;

it('can access the data attribute as a PublicKeyCredentialSource', function () {
    $securityKey = WebauthnKey::factory()->create();

    $securityKey = $securityKey->fresh();

    expect($securityKey->data)->toBeInstanceOf(PublicKeyCredentialSource::class);
});

it('encodes a credential id properly', function () {
    $raw = "\xFF\xFE";

    expect(WebauthnKey::encodeCredentialId($raw))->toBe(Base64UrlSafe::encodeUnpadded($raw));
});
