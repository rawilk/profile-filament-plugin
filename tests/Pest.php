<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Rawilk\ProfileFilament\Enums\Session\MfaSession;
use Rawilk\ProfileFilament\Facades\Sudo;
use Rawilk\ProfileFilament\Facades\Webauthn;
use Rawilk\ProfileFilament\Features;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;
use Rawilk\ProfileFilament\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

// Helpers

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
    return filament()->getCurrentPanel()?->getPlugin(ProfileFilamentPLugin::PLUGIN_ID)->panelFeatures();
}

function login(?Authenticatable $user = null): Authenticatable
{
    $user ??= User::factory()->create();

    test()->actingAs($user);

    return $user;
}

function storeAttestationPublicKeyInSession(Authenticatable $user, ?string $sessionKey = null): void
{
    $publicKey = Webauthn::attestationObjectFor($user->email, $user->id);
    $sessionKey ??= MfaSession::AttestationPublicKey->value;

    session()->put($sessionKey, serialize($publicKey));
}

function queryCount(): int
{
    return count(DB::getQueryLog());
}

function trackQueries(): void
{
    DB::enableQueryLog();
}
