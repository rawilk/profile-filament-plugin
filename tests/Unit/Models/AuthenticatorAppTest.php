<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Date;
use Illuminate\Support\HtmlString;
use Rawilk\ProfileFilament\Models\AuthenticatorApp;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

it('renders when the app was last used in a time html tag', function () {
    Date::setTestNow('2023-01-01 10:00:00');

    $app = AuthenticatorApp::factory()->make(['last_used_at' => now()]);

    expect($app->last_used)->toBeInstanceOf(HtmlString::class)
        ->and($app->last_used->toHtml())
        ->toContain('<time')
        ->toContain('datetime="2023-01-01T10:00:00Z"');
});

it('indicates if an app has never been used', function () {
    $app = AuthenticatorApp::factory()->make(['last_used_at' => null]);

    expect($app->last_used)->toBeInstanceOf(HtmlString::class)
        ->and($app->last_used->toHtml())
        ->toContain(__('profile-filament::pages/security.mfa.method_never_used'))
        ->not->toContain('<time');
});

it('renders when the app was registered in a time html tag', function () {
    Date::setTestNow('2023-01-01 10:00:00');

    $app = AuthenticatorApp::factory()->make(['created_at' => now()]);

    expect($app->registered_at)->toBeInstanceOf(HtmlString::class)
        ->and($app->registered_at->toHtml())
        ->toContain('<time')
        ->toContain('datetime="2023-01-01T10:00:00Z"');
});

it('does not expose the app secret when serialized', function () {
    $app = AuthenticatorApp::factory()->for(User::factory())->create();

    expect($app->toArray())->not->toHaveKey('secret');
});
