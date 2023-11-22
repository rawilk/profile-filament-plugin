<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Authenticatable;
use Rawilk\ProfileFilament\Enums\Session\MfaSession;
use Rawilk\ProfileFilament\Facades\Sudo;
use Rawilk\ProfileFilament\Facades\Webauthn;
use Rawilk\ProfileFilament\Features;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;
use Rawilk\ProfileFilament\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

uses()->beforeEach(function () {
    setupPlugin();
})->in(__DIR__ . '/Feature');

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
    return filament()->getCurrentPanel()?->getPlugin(ProfileFilamentPlugin::make()->getId())->panelFeatures();
}

function setupPlugin(): void
{
    $panel = filament()->getCurrentPanel() ?? filament()->getDefaultPanel();
    if (! $panel) {
        return;
    }

    // I probably don't have something setup right, but this is necessary right now so our pages
    // all get configured properly.
    $panel->getPlugin(ProfileFilamentPlugin::make()->getId())->boot($panel);
}

function login(Authenticatable $user = null): Authenticatable
{
    $user ??= User::factory()->create();

    test()->actingAs($user);

    return $user;
}

function storeAttestationPublicKeyInSession(Authenticatable $user, string $sessionKey = null): void
{
    $publicKey = Webauthn::attestationObjectFor($user->email, $user->id);
    $sessionKey ??= MfaSession::AttestationPublicKey->value;

    session()->put($sessionKey, serialize($publicKey));
}
