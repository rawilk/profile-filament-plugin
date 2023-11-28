<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Features;
use Rawilk\ProfileFilament\Filament\Pages\MfaChallenge;
use Rawilk\ProfileFilament\Filament\Pages\Profile;
use Rawilk\ProfileFilament\Filament\Pages\SudoChallenge;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

beforeEach(function () {
    login($this->user = User::factory()->create());

    $this->plugin = ProfileFilamentPlugin::make();

    $this->plugin->register(filament()->getDefaultPanel());
});

it('knows if sudo mode is enabled', function () {
    // enabled by default
    expect($this->plugin->hasSudoMode())->toBeTrue();

    $this->plugin->features(Features::defaults()->useSudoMode(false));

    expect($this->plugin->hasSudoMode())->toBeFalse();
});

it('knows if a page is enabled', function () {
    // all pages are enabled by default
    expect($this->plugin->isEnabled(Profile::class))->toBeTrue();

    $this->plugin->profile(enabled: false);

    expect($this->plugin->isEnabled(Profile::class))->toBeFalse();
});

test('a custom action can be used for full-page sudo challenge', function () {
    expect($this->plugin->getSudoChallengeAction())->toBe(SudoChallenge::class);

    $this->plugin->challengeSudoWith('foo');

    expect($this->plugin->getSudoChallengeAction())->toBe('foo');
});

test('a custom action can be used for full-page mfa challenge', function () {
    expect($this->plugin->getMfaChallengeAction())->toBe(MfaChallenge::class);

    $this->plugin->challengeMfaWith('foo');

    expect($this->plugin->getMfaChallengeAction())->toBe('foo');
});

it('can get the slug for a page', function () {
    expect($this->plugin->getSlug(Profile::class))->toBe('profile');

    $this->plugin->profile(slug: 'custom-profile');

    expect($this->plugin->getSlug(Profile::class))->toBe('custom-profile');
});

it('can get the url for a page', function () {
    expect($this->plugin->pageUrl(Profile::class))->toBe('https://acme.test/admin/profile');
});
