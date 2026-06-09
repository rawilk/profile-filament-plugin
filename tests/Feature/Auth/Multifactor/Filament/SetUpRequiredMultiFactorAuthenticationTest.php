<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Rawilk\ProfileFilament\Facades\ProfileFilament;
use Rawilk\ProfileFilament\Tests\TestSupport\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Filament::setCurrentPanel('requires-mfa');
});

it('renders the setup page', function () {
    actingAs(User::factory()->create())
        ->get(ProfileFilament::plugin()->getSetUpRequiredMultiFactorAuthenticationUrl())
        ->assertSuccessful();
});

it('redirects user away if they already set up multi-factor authentication', function () {
    actingAs(User::factory()->withMfaEnabled()->create())
        ->get(ProfileFilament::plugin()->getSetUpRequiredMultiFactorAuthenticationUrl())
        ->assertRedirect(Filament::getUrl());
});
