<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Models\AuthenticatorApp;

it('does not expose the app secret when serialized', function () {
    $app = AuthenticatorApp::factory()->create();

    $app->refresh();

    expect($app->toArray())->not->toHaveKey('secret');
});
