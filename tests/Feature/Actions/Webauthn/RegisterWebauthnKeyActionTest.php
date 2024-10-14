<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Rawilk\ProfileFilament\Actions\TwoFactor\MarkTwoFactorEnabledAction;
use Rawilk\ProfileFilament\Actions\Webauthn\RegisterWebauthnKeyAction;
use Rawilk\ProfileFilament\Events\Webauthn\WebauthnKeyRegistered;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Rawilk\ProfileFilament\Testing\Support\FakeWebauthn;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

beforeEach(function () {
    Event::fake();

    config([
        'profile-filament.actions.mark_two_factor_enabled' => MarkTwoFactorEnabledAction::class,
        'profile-filament.models.webauthn_key' => WebauthnKey::class,
    ]);

    $this->user = User::factory()->withoutMfa()->create();
});

it('registers a new webauthn key for a user', function () {
    $record = app(RegisterWebauthnKeyAction::class)(
        user: $this->user,
        publicKeyCredentialSource: FakeWebauthn::publicKeyCredentialSource(encodeUserId: false),
        attestation: FakeWebauthn::attestationResponse(),
        keyName: 'my key',
    );

    Event::assertDispatched(function (WebauthnKeyRegistered $event) use ($record) {
        expect($event->user)->toBe($this->user)
            ->and($event->webauthnKey)->toBe($record);

        return true;
    });

    expect($record->refresh())
        ->name->toBe('my key')
        ->is_passkey->toBeFalse()
        ->user->toBe($this->user);
});

it('calls the action to enable mfa for a user', function () {
    $this->mock(MarkTwoFactorEnabledAction::class)
        ->shouldReceive('__invoke')
        ->with($this->user)
        ->once();

    app(RegisterWebauthnKeyAction::class)(
        user: $this->user,
        publicKeyCredentialSource: FakeWebauthn::publicKeyCredentialSource(encodeUserId: false),
        attestation: FakeWebauthn::attestationResponse(),
        keyName: 'my key',
    );
});
