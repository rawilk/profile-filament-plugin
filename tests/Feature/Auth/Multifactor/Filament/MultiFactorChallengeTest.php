<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Illuminate\Support\Facades\RateLimiter;
use Rawilk\ProfileFilament\Auth\Multifactor\App\AppAuthenticationProvider;
use Rawilk\ProfileFilament\Auth\Multifactor\Facades\Mfa;
use Rawilk\ProfileFilament\Auth\Multifactor\Filament\MultiFactorChallenge;
use Rawilk\ProfileFilament\Models\AuthenticatorApp;
use Rawilk\ProfileFilament\Tests\TestSupport\Models\User;

use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertGuest;
use function Pest\Livewire\livewire;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
});

it('authenticates a valid challenge while attempts remain', function () {
    $this->freezeSecond();

    $user = User::factory()
        ->withMfaEnabled()
        ->has(AuthenticatorApp::factory())
        ->create();

    Mfa::pushChallengedUser($user);

    $provider = getPlugin()->getMultiFactorAuthenticationProvider(AppAuthenticationProvider::ID);
    $code = $provider->getCurrentCode($user->authenticatorApps->first()->secret);

    livewire(MultiFactorChallenge::class)
        ->fillForm([$provider->getId() => ['code' => $code]])
        ->call('authenticate')
        ->assertHasNoErrors()
        ->assertRedirect(Filament::getUrl());

    assertAuthenticatedAs($user);
});

it('blocks a valid challenge after five attempts until the limit expires', function () {
    $this->freezeSecond();

    $user = User::factory()
        ->withMfaEnabled()
        ->has(AuthenticatorApp::factory())
        ->create();

    Mfa::pushChallengedUser($user);

    $provider = getPlugin()->getMultiFactorAuthenticationProvider(AppAuthenticationProvider::ID);
    $rateLimitKey = "pf-multi-factor-challenge:{$user->getAuthIdentifier()}";

    RateLimiter::increment($rateLimitKey, amount: 5);

    livewire(MultiFactorChallenge::class)
        ->fillForm([
            $provider->getId() => [
                'code' => $provider->getCurrentCode($user->authenticatorApps->first()->secret),
            ],
        ])
        ->call('authenticate')
        ->assertNotified()
        ->assertNoRedirect();

    assertGuest();

    $this->travel(61)->seconds();

    livewire(MultiFactorChallenge::class)
        ->fillForm([
            $provider->getId() => [
                'code' => $provider->getCurrentCode($user->authenticatorApps->first()->secret),
            ],
        ])
        ->call('authenticate')
        ->assertHasNoErrors()
        ->assertRedirect(Filament::getUrl());

    assertAuthenticatedAs($user);
});
