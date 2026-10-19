<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Enums\WebauthnSession;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Filament\Pages\CrossDomainWebauthnAuth;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\WebauthnProvider;
use Rawilk\ProfileFilament\Tests\TestSupport\Models\User;

it('blocks cross-domain passkey responses after five attempts', function () {
    Filament::setCurrentPanel('admin');

    $user = User::factory()->create();
    $rateLimitKey = "pf-cross-domain-webauthn-auth:{$user->getAuthIdentifier()}";

    RateLimiter::increment($rateLimitKey, amount: 5);

    Livewire::withQueryParams([
        'user' => Crypt::encrypt((string) $user->getAuthIdentifier()),
        'providerId' => WebauthnProvider::ID,
        'origin' => 'acme.test',
        'passkey' => 1,
        'nonce' => 'test-nonce',
    ])
        ->test(CrossDomainWebauthnAuth::class)
        ->call('authenticate', ['authenticationResponse' => '{}'])
        ->assertNotified();

    expect(WebauthnSession::AuthenticationOptions->get())->not->toBeNull();
});
