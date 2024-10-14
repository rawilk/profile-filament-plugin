<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Rawilk\ProfileFilament\Actions\Passkeys\UpgradeToPasskeyAction;
use Rawilk\ProfileFilament\Events\TwoFactorAuthenticationWasEnabled;
use Rawilk\ProfileFilament\Events\Webauthn\WebauthnKeyUpgradeToPasskey;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Rawilk\ProfileFilament\Testing\Support\FakeWebauthn;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

beforeEach(function () {
    Event::fake();

    $this->record = WebauthnKey::factory()
        ->notPasskey()
        ->for(User::factory()->withMfa()->create(['id' => 1]))
        ->create([
            'attachment_type' => 'platform',
        ]);
});

it('can upgrade a webauthn key to a passkey', function () {
    Cache::shouldReceive('forget')->with('user:1:has-passkeys')->once();

    $passkey = app(UpgradeToPasskeyAction::class)(
        user: $this->record->user,
        publicKeyCredentialSource: FakeWebauthn::publicKeyCredentialSource(encodeUserId: false),
        attestation: FakeWebauthn::attestationResponse(),
        webauthnKey: $this->record,
    );

    Event::assertDispatched(function (WebauthnKeyUpgradeToPasskey $event) use ($passkey) {
        expect($event->upgradedFrom)->toBe($this->record)
            ->and($event->passkey)->toBe($passkey)
            ->and($event->user)->toBe($this->record->user);

        return true;
    });

    Event::assertNotDispatched(TwoFactorAuthenticationWasEnabled::class);

    $this->assertModelMissing($this->record);

    expect($passkey)
        ->is_passkey->toBeTrue()
        ->name->toBe($this->record->name)
        ->user->toBe($this->record->user);
});
