<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Rawilk\ProfileFilament\Enums\Session\MfaSession;
use Rawilk\ProfileFilament\Enums\Session\SudoSession;
use Rawilk\ProfileFilament\Facades\Sudo;
use Rawilk\ProfileFilament\Facades\Webauthn;
use Rawilk\ProfileFilament\Features;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;
use Rawilk\ProfileFilament\Tests\TestCase;

pest()->extend(TestCase::class)
    ->in(
        'Feature',
        'Unit',
    );

pest()->uses(
    LazilyRefreshDatabase::class,
)->in(
    'Feature',
);

// Helpers

function challengeUser(?User $user = null): void
{
    $user ??= test()->user;

    session()->put(MfaSession::User->value, $user->getKey());
}

function queryCount(): int
{
    return count(DB::getQueryLog());
}

function trackQueries(): void
{
    DB::enableQueryLog();
}

function enableSudoMode(bool $resetSudo = true): void
{
    getPanelFeatures()->useSudoMode();

    if ($resetSudo) {
        Sudo::deactivate();
    }
}

function disableSudoMode(): void
{
    getPanelFeatures()->useSudoMode(false);
}

function getPanelFeatures(): Features
{
    return filament()->getCurrentPanel()?->getPlugin(ProfileFilamentPlugin::PLUGIN_ID)->panelFeatures();
}

function login(?Authenticatable $user = null): Authenticatable
{
    $user ??= User::factory()->create();

    test()->actingAs($user);

    return $user;
}

function disableMfa(User $user): void
{
    $user->update([
        'two_factor_enabled' => false,
        'two_factor_recovery_codes' => null,
    ]);
}

function enableMfa(User $user): void
{
    $user->update([
        'two_factor_enabled' => true,
        'two_factor_recovery_codes' => Crypt::encryptString(
            json_encode([
                'code-one',
                'code-two',
                'code-three',
                'code-four',
            ])
        ),
    ]);
}

function storeAttestationOptionsInSession(User $user, ?string $sessionKey = null): void
{
    $options = Webauthn::attestationObjectFor($user);

    $sessionKey ??= MfaSession::AttestationPublicKey->value;

    session()->put($sessionKey, $options);
}

function storeAssertionOptionsInSession(User $user, ?string $sessionKey = null): void
{
    $options = Webauthn::assertionObjectFor($user);

    $sessionKey ??= SudoSession::WebauthnAssertionPk->value;

    session()->put($sessionKey, $options);
}

function storePasskeyAttestationOptionsInSession(User $user, ?string $sessionKey = null): void
{
    $options = Webauthn::passkeyAttestationObjectFor($user);

    $sessionKey ??= MfaSession::PasskeyAttestationPk->value;

    session()->put($sessionKey, $options);
}

function sudoChallengeTitle(): string
{
    return __('profile-filament::messages.sudo_challenge.title');
}
