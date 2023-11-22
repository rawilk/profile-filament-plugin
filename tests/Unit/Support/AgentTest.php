<?php

declare(strict_types=1);

/**
 * @copyright Originally created by Jens Segers: https://github.com/jenssegers/agent
 * @copyright Originally created by Laravel Jetstream: https://github.com/laravel/jetstream
 */

use Rawilk\ProfileFilament\Support\Agent;

test('operating systems', function (string $userAgent, string $platform) {
    $agent = new Agent;
    $agent->setUserAgent($userAgent);

    expect($agent->platform())->toBe($platform);
})->with('operatingSystems');

test('browsers', function (string $userAgent, string $browser) {
    $agent = new Agent;
    $agent->setUserAgent($userAgent);

    expect($agent->browser())->toBe($browser);
})->with('browsers');

test('desktop devices', function (string $userAgent, bool $isDesktop) {
    $agent = new Agent;
    $agent->setUserAgent($userAgent);

    expect($agent->isDesktop())->toBe($isDesktop);
})->with('desktopDevices');
