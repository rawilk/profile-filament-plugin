<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Rawilk\ProfileFilament\Auth\Multifactor\Http\Middleware\EnsureMultiFactorAuthenticationIsEnabled;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Tests\TestSupport\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Route::get('/requires-mfa/test-url', fn () => 'ok')
        ->middleware([
            'web',
            'auth',
            EnsureMultiFactorAuthenticationIsEnabled::class,
        ]);

    Filament::setCurrentPanel('requires-mfa');
});

it('redirects users that need to enable multi-factor authentication', function () {
    actingAs(User::factory()->create())
        ->get('/requires-mfa/test-url')
        ->assertRedirect(filament(ProfileFilamentPlugin::PLUGIN_ID)->getSetUpRequiredMultiFactorAuthenticationUrl());
});

it('does not redirect users that have multi-factor authentication enabled', function () {
    actingAs(User::factory()->withMfaEnabled()->create())
        ->get('/requires-mfa/test-url')
        ->assertSuccessful()
        ->assertSeeText('ok');
});

test('the panel redirects any authenticated route to the set up page if needed', function () {
    actingAs(User::factory()->create())
        ->get(Filament::getUrl())
        ->assertRedirect(filament(ProfileFilamentPlugin::PLUGIN_ID)->getSetUpRequiredMultiFactorAuthenticationUrl());
});
