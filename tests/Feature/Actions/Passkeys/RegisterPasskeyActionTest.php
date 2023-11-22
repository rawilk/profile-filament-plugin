<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Rawilk\ProfileFilament\Actions\Passkeys\RegisterPasskeyAction;
use Rawilk\ProfileFilament\Actions\TwoFactor\MarkTwoFactorEnabledAction;
use Rawilk\ProfileFilament\Events\Passkeys\PasskeyRegistered;
use Rawilk\ProfileFilament\Events\TwoFactorAuthenticationWasEnabled;
use Rawilk\ProfileFilament\Facades\Webauthn as WebauthnFacade;
use Rawilk\ProfileFilament\Services\Webauthn;
use Rawilk\ProfileFilament\Testing\Support\FakeWebauthn;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

beforeEach(function () {
    Event::fake();

    config([
        'profile-filament.actions.mark_two_factor_enabled' => MarkTwoFactorEnabledAction::class,
    ]);

    $this->user = User::factory()->withoutMfa()->create();
});

it('registers a new passkey for a user', function () {
    Webauthn::generateChallengeWith(fn (): string => FakeWebauthn::rawAttestationChallenge());

    $publicKey = WebauthnFacade::passkeyAttestationObjectFor($this->user->email, $this->user->id);
    $publicKeyCredentialSource = WebauthnFacade::verifyAttestation(
        FakeWebauthn::attestationResponse(),
        $publicKey,
    );

    Cache::shouldReceive('forget')->with('user:1:has-passkeys');

    $passkey = app(RegisterPasskeyAction::class)(
        user: $this->user,
        publicKeyCredentialSource: $publicKeyCredentialSource,
        attestation: FakeWebauthn::attestationResponse(),
        keyName: 'my passkey',
    );

    Event::assertDispatched(PasskeyRegistered::class);
    Event::assertDispatched(TwoFactorAuthenticationWasEnabled::class);

    expect($passkey)
        ->name->toBe('my passkey')
        ->is_passkey->toBeTrue()
        ->user
        ->toBe($this->user)
        ->and($this->user->refresh())
        ->two_factor_enabled->toBeTrue();
});

it('does not try to enable 2fa if the user already has it enabled', function () {
    Webauthn::generateChallengeWith(fn (): string => FakeWebauthn::rawAttestationChallenge());

    $this->user->update(['two_factor_enabled' => true]);

    $publicKey = WebauthnFacade::passkeyAttestationObjectFor($this->user->email, $this->user->id);
    $publicKeyCredentialSource = WebauthnFacade::verifyAttestation(
        FakeWebauthn::attestationResponse(),
        $publicKey,
    );

    Cache::shouldReceive('forget')->with('user:1:has-passkeys');

    app(RegisterPasskeyAction::class)(
        user: $this->user,
        publicKeyCredentialSource: $publicKeyCredentialSource,
        attestation: FakeWebauthn::attestationResponse(),
        keyName: 'my passkey',
    );

    Event::assertDispatched(PasskeyRegistered::class);
    Event::assertNotDispatched(TwoFactorAuthenticationWasEnabled::class);
});
