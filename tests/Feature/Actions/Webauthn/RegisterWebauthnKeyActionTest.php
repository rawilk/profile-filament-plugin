<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Rawilk\ProfileFilament\Actions\TwoFactor\MarkTwoFactorEnabledAction;
use Rawilk\ProfileFilament\Actions\Webauthn\RegisterWebauthnKeyAction;
use Rawilk\ProfileFilament\Events\TwoFactorAuthenticationWasEnabled;
use Rawilk\ProfileFilament\Events\Webauthn\WebauthnKeyRegistered;
use Rawilk\ProfileFilament\Facades\Webauthn as WebauthnFacade;
use Rawilk\ProfileFilament\Services\Webauthn;
use Rawilk\ProfileFilament\Testing\Support\FakeWebauthn;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

beforeEach(function () {
    Event::fake();

    config([
        'profile-filament.actions.mark_two_factor_enabled' => MarkTwoFactorEnabledAction::class,
    ]);

    Webauthn::generateChallengeWith(null);

    $this->user = User::factory()->withoutMfa()->create();
});

it('registers a webauthn key to a user account', function () {
    Webauthn::generateChallengeWith(fn (): string => FakeWebauthn::rawAttestationChallenge());

    $publicKey = WebauthnFacade::attestationObjectFor($this->user->email, $this->user->id);
    $publicKeyCredentialSource = WebauthnFacade::verifyAttestation(
        FakeWebauthn::attestationResponse(),
        $publicKey,
    );

    $webauthnKey = app(RegisterWebauthnKeyAction::class)(
        user: $this->user,
        publicKeyCredentialSource: $publicKeyCredentialSource,
        attestation: FakeWebauthn::attestationResponse(),
        keyName: 'my key',
    );

    Event::assertDispatched(WebauthnKeyRegistered::class);
    Event::assertDispatched(TwoFactorAuthenticationWasEnabled::class);

    $webauthnKey->refresh();

    expect($webauthnKey->user)->toBe($this->user)
        ->and($webauthnKey->name)->toBe('my key')
        ->and($webauthnKey->is_passkey)->toBeFalse()
        ->and($this->user->refresh())
        ->two_factor_enabled->toBeTrue();
});

it('does not enable 2fa if the user already has it enabled', function () {
    Webauthn::generateChallengeWith(fn (): string => FakeWebauthn::rawAttestationChallenge());

    $this->user->update(['two_factor_enabled' => true]);

    $publicKey = WebauthnFacade::attestationObjectFor($this->user->email, $this->user->id);
    $publicKeyCredentialSource = WebauthnFacade::verifyAttestation(
        FakeWebauthn::attestationResponse(),
        $publicKey,
    );

    app(RegisterWebauthnKeyAction::class)(
        user: $this->user,
        publicKeyCredentialSource: $publicKeyCredentialSource,
        attestation: FakeWebauthn::attestationResponse(),
        keyName: 'my key',
    );

    Event::assertNotDispatched(TwoFactorAuthenticationWasEnabled::class);
});
