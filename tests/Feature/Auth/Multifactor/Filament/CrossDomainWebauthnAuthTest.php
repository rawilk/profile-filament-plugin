<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Js;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Actions\FindSecurityKeyToAuthenticateAction;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Enums\WebauthnSession;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Filament\Pages\CrossDomainWebauthnAuth;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\WebauthnProvider;
use Rawilk\ProfileFilament\Auth\Sudo\Webauthn\SudoWebauthnProvider;
use Rawilk\ProfileFilament\Facades\ProfileFilament;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Rawilk\ProfileFilament\Tests\TestSupport\Models\User;

it('blocks cross-domain passkey responses after five attempts', function () {
    Filament::setCurrentPanel('admin');

    $user = User::factory()->create();
    $rateLimitKey = 'pf-cross-domain-webauthn-auth:passkey:127.0.0.1';

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

it('resolves the sudo provider before generating external authentication options', function () {
    Filament::setCurrentPanel('admin');
    $user = User::factory()->create();

    $component = Livewire::withQueryParams([
        'user' => Crypt::encrypt($user->getAuthIdentifier()),
        'providerId' => 'webauthn',
        'origin' => 'acme.test',
        'passkey' => 0,
        'sudo' => 1,
        'nonce' => 'test-nonce',
    ])->test(CrossDomainWebauthnAuth::class);

    expect($component->instance()->providerInstance)->toBeInstanceOf(SudoWebauthnProvider::class);
});

it('never issues a completion for invalid or missing assertions', function (mixed $response) {
    Filament::setCurrentPanel('admin');
    $user = User::factory()->create();
    $nonce = ProfileFilament::generateWebauthnNonce('mfa:webauthn:' . $user->getAuthIdentifier());

    Livewire::withQueryParams([
        'user' => Crypt::encrypt($user->getAuthIdentifier()),
        'providerId' => 'webauthn',
        'origin' => 'acme.test',
        'passkey' => 0,
        'nonce' => $nonce,
    ])->test(CrossDomainWebauthnAuth::class)
        ->call('authenticate', ['authenticationResponse' => $response])
        ->assertDispatched('webauthnAuthenticationFailed');

    expect(cache()->has(ProfileFilament::crossDomainChallengeCacheKey($user, $nonce)))->toBeFalse();
})->with([[null], [''], [[]], ['invalid'], ['{}'], ['[]'], ['null']]);

it('supports userless passkey responses and serializes browser data safely', function () {
    Filament::setCurrentPanel('admin');
    $response = ['id' => '</script><script>alert(1)</script>'];

    $component = Livewire::withQueryParams([
        'user' => 'not-an-encrypted-user-id',
        'providerId' => 'webauthn',
        'origin' => 'acme.test',
        'passkey' => 1,
        'nonce' => 'test-nonce',
    ])->test(CrossDomainWebauthnAuth::class)
        ->call('authenticate', ['authenticationResponse' => json_encode($response)])
        ->assertNotDispatched('webauthnAuthenticationFailed');

    $scripts = implode("\n", array_column($component->effects['xjs'], 'expression'));

    expect($scripts)->toContain((string) Js::from((object) $response))
        ->not->toContain('</script>')
        ->and(WebauthnSession::AuthenticationOptions->get())->toBeNull();
});

it('issues a completion only after the assertion verifier accepts the correct user', function (bool $accepted) {
    Filament::setCurrentPanel('admin');
    $user = User::factory()->create();
    $nonce = ProfileFilament::generateWebauthnNonce('mfa:webauthn:' . $user->getAuthIdentifier());
    $key = WebauthnKey::factory()
        ->for($user)
        ->create();

    $this->mock(FindSecurityKeyToAuthenticateAction::class)
        ->shouldReceive('__invoke')
        ->once()
        ->withArgs(fn ($response, $options, $passkey, $challengedUser) => $response === '{}' && is_string($options) && $passkey === false && $challengedUser->is($user))
        ->andReturn($accepted ? $key : null);

    $component = Livewire::withQueryParams([
        'user' => Crypt::encrypt($user->getAuthIdentifier()),
        'providerId' => 'webauthn',
        'origin' => 'acme.test',
        'passkey' => 0,
        'nonce' => $nonce,
    ])->test(CrossDomainWebauthnAuth::class)
        ->call('authenticate', ['authenticationResponse' => '{}']);

    expect(cache()->has(ProfileFilament::crossDomainChallengeCacheKey($user, $nonce)))->toBe($accepted)
        ->and(WebauthnSession::AuthenticationOptions->get())->toBeNull();

    if (! $accepted) {
        $component
            ->assertNoJs()
            ->assertDispatched('webauthnAuthenticationFailed');
    }
})->with([true, false]);

it('prevents clients from replacing locked ceremony fields', function (string $field, mixed $value) {
    Filament::setCurrentPanel('admin');
    $user = User::factory()->create();

    Livewire::withQueryParams([
        'user' => Crypt::encrypt($user->getAuthIdentifier()),
        'providerId' => 'webauthn',
        'origin' => 'acme.test',
        'passkey' => 0,
        'nonce' => 'test-nonce',
    ])->test(CrossDomainWebauthnAuth::class)
        ->set($field, $value);
})->with([
    ['userId', 'replacement'],
    ['providerId', 'app'],
    ['origin', 'attacker.test'],
    ['nonce', 'replacement'],
    ['isSudo', true],
    ['passkeysOnly', true],
])->throws(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);

it('rejects unsigned or tampered external authentication URLs', function () {
    Filament::setCurrentPanel('admin');
    config()->set('profile-filament.webauthn.relying_party.id', 'keys.test');
    $user = User::factory()->create();
    $url = getPlugin()->getCrossDomainWebauthnAuthenticationUrl($user, 'acme.test', [
        'providerId' => 'webauthn',
        'nonce' => ProfileFilament::generateWebauthnNonce('mfa:webauthn:' . $user->getAuthIdentifier()),
        'passkey' => 0,
    ]);

    $this->get($url)->assertOk();
    $this->get($url . '&sudo=1')->assertForbidden();
    $this->get(explode('?', $url)[0])->assertForbidden();
});

it('does not issue empty completion challenges', function () {
    Filament::setCurrentPanel('admin');
    $user = User::factory()->create();
    $page = app(CrossDomainWebauthnAuth::class);
    $page->userId = Crypt::encrypt($user->getAuthIdentifier());
    $page->nonce = 'test-nonce';
    Str::createRandomStringsUsing(fn () => '');

    try {
        (fn () => $this->getChallengeForOrigin())->call($page);
    } finally {
        Str::createRandomStringsNormally();
        expect(cache()->has(ProfileFilament::crossDomainChallengeCacheKey($user, $page->nonce)))->toBeFalse();
    }
})->throws(LogicException::class);
