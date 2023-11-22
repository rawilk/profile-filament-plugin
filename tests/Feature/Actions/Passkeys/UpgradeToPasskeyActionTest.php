<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Rawilk\ProfileFilament\Actions\Passkeys\UpgradeToPasskeyAction;
use Rawilk\ProfileFilament\Events\TwoFactorAuthenticationWasEnabled;
use Rawilk\ProfileFilament\Events\Webauthn\WebauthnKeyUpgradeToPasskey;
use Rawilk\ProfileFilament\Facades\Webauthn as WebauthnFacade;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Rawilk\ProfileFilament\Services\Webauthn;
use Rawilk\ProfileFilament\Testing\Support\FakeWebauthn;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

beforeEach(function () {
    Event::fake();

    Webauthn::generateChallengeWith(null);

    $this->user = User::factory()->withMfa()->create();
    $this->webauthnKey = WebauthnKey::factory()->notPasskey()->for($this->user)->create();
});

it('can upgrade a webauthn key to a passkey', function () {
    Webauthn::generateChallengeWith(fn (): string => FakeWebauthn::rawAttestationChallenge());

    $publicKey = WebauthnFacade::passkeyAttestationObjectFor($this->user->email, $this->user->id);
    $publicKeyCredentialSource = WebauthnFacade::verifyAttestation(
        FakeWebauthn::attestationResponse(),
        $publicKey,
    );

    Cache::shouldReceive('forget')->with('user:1:has-passkeys');

    app(UpgradeToPasskeyAction::class)(
        user: $this->user,
        publicKeyCredentialSource: $publicKeyCredentialSource,
        attestation: FakeWebauthn::attestationResponse(),
        webauthnKey: $this->webauthnKey,
    );

    Event::assertDispatched(function (WebauthnKeyUpgradeToPasskey $event) {
        expect($event->upgradedFrom)->toBe($this->webauthnKey)
            ->and($event->passkey->name)->toBe($this->webauthnKey->name)
            ->and($event->passkey)->not->toBe($this->webauthnKey);

        return true;
    });

    Event::assertNotDispatched(TwoFactorAuthenticationWasEnabled::class);

    $this->assertDatabaseMissing(WebauthnKey::class, [
        'id' => $this->webauthnKey->id,
    ]);

    $this->assertDatabaseHas(WebauthnKey::class, [
        'is_passkey' => true,
        'name' => $this->webauthnKey->name,
        'user_id' => $this->user->id,
    ]);
});
