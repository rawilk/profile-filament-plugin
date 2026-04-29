<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Tests\TestSupport\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Filament::setCurrentPanel('requires-mfa');
});

it('renders the setup page', function () {
    actingAs(User::factory()->create())
        ->get(filament(ProfileFilamentPlugin::PLUGIN_ID)->getSetUpRequiredMultiFactorAuthenticationUrl())
        ->assertSuccessful();
});

it('redirects user away if they already set up multi-factor authentication', function () {
    actingAs(User::factory()->withMfaEnabled()->create())
        ->get(filament(ProfileFilamentPlugin::PLUGIN_ID)->getSetUpRequiredMultiFactorAuthenticationUrl())
        ->assertRedirect(Filament::getUrl());
});
