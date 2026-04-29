<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Auth\Sudo\Enums\SudoSession;
use Rawilk\ProfileFilament\Auth\Sudo\Services\Sudo;

beforeEach(function () {
    config()->set('profile-filament.sudo.expires', DateInterval::createFromDateString('2 hours'));

    $this->service = app(Sudo::class);
    $this->freezeSecond();
});

it('can be activated', function () {
    $this->service->activate();

    expect(now())->toBeSudoSessionValue();
});

it('can be extended', function () {
    $this->service->activate();

    $this->travel(1)->hour();

    expect(now())->not->toBeSudoSessionValue();

    $this->service->extend();

    expect(now())->toBeSudoSessionValue();
});

it('can be deactivated', function () {
    $this->service->activate();

    expect(SudoSession::ConfirmedAt->has())->toBeTrue();

    $this->service->deactivate();

    expect(now())->not->toBeSudoSessionValue()
        ->and(SudoSession::ConfirmedAt->has())->toBeFalse();
});

it('can determine if the sudo session is valid', function () {
    $this->service->activate();
    expect($this->service->isValid())->toBeTrue();

    $this->travelTo(now()->addHours(2)->subSecond());
    expect($this->service->isValid())->toBeTrue();

    $this->travel(1)->second();
    expect($this->service->isValid())->toBeFalse();
});
