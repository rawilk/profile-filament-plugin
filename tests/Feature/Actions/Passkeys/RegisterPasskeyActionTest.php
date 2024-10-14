<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Rawilk\ProfileFilament\Actions\Passkeys\RegisterPasskeyAction;
use Rawilk\ProfileFilament\Actions\TwoFactor\MarkTwoFactorEnabledAction;
use Rawilk\ProfileFilament\Events\Passkeys\PasskeyRegistered;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Rawilk\ProfileFilament\Testing\Support\FakeWebauthn;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

beforeEach(function () {
    Event::fake();

    config([
        'profile-filament.actions.mark_two_factor_enabled' => MarkTwoFactorEnabledAction::class,
        'profile-filament.models.webauthn_key' => WebauthnKey::class,
    ]);

    $this->user = User::factory()->withoutMfa()->create(['id' => 1]);
});

it('registers a new passkey for a user', function () {
    Cache::shouldReceive('forget')->with('user:1:has-passkeys')->once();

    $passkey = app(RegisterPasskeyAction::class)(
        user: $this->user,
        publicKeyCredentialSource: FakeWebauthn::publicKeyCredentialSource(encodeUserId: false),
        attestation: FakeWebauthn::attestationResponse(),
        keyName: 'my passkey',
    );

    Event::assertDispatched(function (PasskeyRegistered $event) use ($passkey) {
        expect($event->passkey)->toBe($passkey)
            ->and($event->user)->toBe($this->user);

        return true;
    });

    expect($passkey)
        ->name->toBe('my passkey')
        ->is_passkey->toBeTrue()
        ->user
        ->toBe($this->user);
});

it('calls the action to enable mfa for a user', function () {
    $this->mock(MarkTwoFactorEnabledAction::class)
        ->shouldReceive('__invoke')
        ->with($this->user)
        ->once();

    app(RegisterPasskeyAction::class)(
        user: $this->user,
        publicKeyCredentialSource: FakeWebauthn::publicKeyCredentialSource(encodeUserId: false),
        attestation: FakeWebauthn::attestationResponse(),
        keyName: 'my passkey',
    );
});
