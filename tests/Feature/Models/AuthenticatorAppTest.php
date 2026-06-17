<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Auth\Multifactor\App\AppAuthenticationProvider;
use Rawilk\ProfileFilament\Models\AuthenticatorApp;

it('does not expose the app secret when serialized', function () {
    $app = AuthenticatorApp::factory()->create();

    $app->refresh();

    expect($app->toArray())->not->toHaveKey('secret');
});

it('generates a valid secret from the factory', function () {
    $app = AuthenticatorApp::factory()->create();

    /** @var AppAuthenticationProvider $provider */
    $provider = app(AppAuthenticationProvider::class);

    expect($provider->getCurrentCode($app->secret))->toBeString()->toHaveLength(6);
});
