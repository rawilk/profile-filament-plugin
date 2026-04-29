<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Rawilk\ProfileFilament\Auth\Multifactor\Enums\MfaSession;
use Rawilk\ProfileFilament\Auth\Sudo\Password\SudoPasswordProvider;
use Rawilk\ProfileFilament\Filament\Pages\Profile\Settings;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Tests\TestCase;
use Rawilk\ProfileFilament\Tests\TestSupport\Models\User;

pest()->uses(TestCase::class)->in(__DIR__);

pest()->uses(LazilyRefreshDatabase::class)->in('Feature');

// Helpers

function challengeUser(User $user): void
{
    MfaSession::UserBeingAuthenticated->set((string) $user->getAuthIdentifier());
    MfaSession::PasswordConfirmedAt->set(now());
}

function queryCount(): int
{
    return count(DB::getQueryLog());
}

function trackQueries(): void
{
    DB::enableQueryLog();
}

function getPlugin(?Panel $panel = null): ProfileFilamentPlugin
{
    $panel ??= Filament::getCurrentOrDefaultPanel();

    return $panel->getPlugin(ProfileFilamentPlugin::PLUGIN_ID);
}

function disableSudoMode(): void
{
    $plugin = getPlugin();

    $plugin->sudoMode(false);

    (fn () => $this->sudoChallengeProviderCache = [])->call($plugin);
}

function enableSudoMode(?array $providers = null): void
{
    $plugin = getPlugin();

    $providers ??= [
        SudoPasswordProvider::make(),
    ];

    (fn () => $this->sudoChallengeProviderCache = [])->call($plugin);

    $plugin->sudoMode($providers);
}

function getProfileSettingsUrl(): string
{
    return filament(ProfileFilamentPlugin::PLUGIN_ID)->getPageUrl(Settings::class);
}
