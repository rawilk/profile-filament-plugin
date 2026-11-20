<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Enums\WebauthnSession;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Filament\Pages\CrossDomainWebauthnAuth;
use Rawilk\ProfileFilament\Facades\ProfileFilament;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Rawilk\ProfileFilament\Tests\TestSupport\Models\User;
use Symfony\Component\Uid\Uuid;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\TrustPath\EmptyTrustPath;

it('validates real signed assertions before issuing external completions', function (string $variation, bool $sudo) {
    Filament::setCurrentPanel('admin');
    config()->set('profile-filament.webauthn.relying_party.id', 'keys.test');
    $user = User::factory()->create();
    $privateKey = openssl_pkey_new([
        'private_key_type' => OPENSSL_KEYTYPE_EC,
        'curve_name' => 'prime256v1',
        // Older PHP versions validate this option even though the curve determines the key size.
        'private_key_bits' => 384,
    ]);
    $publicKey = openssl_pkey_get_details($privateKey)['ec'];
    $credentialId = 'test-credential';
    $userHandle = (string) $user->getAuthIdentifier();
    $encode = fn (string $bytes): string => rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');

    // COSE EC2 key: kty=2, alg=-7 (ES256), crv=1 (P-256), and the public coordinates.
    $coseKey = hex2bin('a5010203262001215820') . $publicKey['x'] . hex2bin('225820') . $publicKey['y'];
    WebauthnKey::factory()->for($variation === 'other-user' ? User::factory()->create() : $user)->create([
        'data' => new PublicKeyCredentialSource(
            publicKeyCredentialId: $credentialId,
            type: 'public-key',
            transports: [],
            attestationType: 'none',
            trustPath: EmptyTrustPath::create(),
            aaguid: Uuid::fromString('00000000-0000-0000-0000-000000000000'),
            credentialPublicKey: $coseKey,
            userHandle: $userHandle,
            counter: 0,
        ),
    ]);

    $context = ($sudo ? 'sudo' : 'mfa') . ':webauthn';
    $nonce = ProfileFilament::generateWebauthnNonce($context . ':' . $userHandle);
    $encryptedUserId = Crypt::encrypt($user->getAuthIdentifier());
    $component = Livewire::withQueryParams([
        'user' => $encryptedUserId,
        'providerId' => 'webauthn',
        'origin' => 'acme.test',
        'passkey' => 0,
        'sudo' => $sudo,
        'nonce' => $nonce,
    ])->test(CrossDomainWebauthnAuth::class);

    $options = json_decode(WebauthnSession::AuthenticationOptions->get(), true);
    $clientData = json_encode([
        'type' => 'webauthn.get',
        'challenge' => $variation === 'wrong-challenge' ? $encode('different-challenge') : $options['challenge'],
        'origin' => $variation === 'wrong-origin' ? 'https://attacker.test' : 'https://keys.test',
        'crossOrigin' => false,
    ]);
    // User presence and verification flags; counter advances to one.
    $authenticatorData = hash('sha256', $variation === 'wrong-rp' ? 'attacker.test' : 'keys.test', true) . chr(5) . pack('N', 1);
    openssl_sign($authenticatorData . hash('sha256', $clientData, true), $signature, $privateKey, OPENSSL_ALGO_SHA256);

    $response = json_encode([
        'id' => $encode($credentialId),
        'rawId' => $encode($credentialId),
        'type' => 'public-key',
        'response' => [
            'clientDataJSON' => $encode($clientData),
            'authenticatorData' => $encode($authenticatorData),
            'signature' => $encode($variation === 'wrong-signature' ? str_repeat('x', 70) : $signature),
            'userHandle' => $encode($variation === 'wrong-handle' ? 'different-user' : $userHandle),
        ],
        'clientExtensionResults' => (object) [],
    ]);

    $component->call('authenticate', ['authenticationResponse' => $response]);
    $challenge = cache()->get(ProfileFilament::crossDomainChallengeCacheKey($user, $nonce));

    if ($variation !== 'valid') {
        $component->assertDispatched('webauthnAuthenticationFailed')->assertNoJs();
        expect($challenge)->toBeNull();

        return;
    }

    $component->assertNotDispatched('webauthnAuthenticationFailed');
    expect($challenge)->toBeString()->not->toBeEmpty()
        ->and(ProfileFilament::verifyCrossDomainRequest([
            'userId' => $encryptedUserId,
            'nonce' => $nonce,
            'challenge' => Hash::make($challenge),
        ], $user, $context))->toBeTrue();

    // The assertion options were consumed; even an identical signed assertion cannot issue another completion.
    $component->call('authenticate', ['authenticationResponse' => $response])
        ->assertDispatched('webauthnAuthenticationFailed')
        ->assertNoJs();

    expect(cache()->has(ProfileFilament::crossDomainChallengeCacheKey($user, $nonce)))->toBeFalse();
})->with(['valid', 'wrong-challenge', 'wrong-origin', 'wrong-rp', 'wrong-signature', 'wrong-handle', 'other-user'])
    ->with([false, true]);
