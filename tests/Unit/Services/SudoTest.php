<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Enums\Session\SudoSession;
use Rawilk\ProfileFilament\Services\Sudo;

beforeEach(function () {
    $this->sudo = new Sudo(
        expiration: DateInterval::createFromDateString('2 hours'),
    );

    $this->freezeSecond();
});

it('can be activated', function () {
    $this->sudo->activate();

    expect(now())->toBeSudoSessionValue();
});

it('can be extended', function () {
    $this->sudo->activate();

    $this->travel(1)->hour();

    expect(now())->not->toBeSudoSessionValue();

    $this->sudo->extend();

    expect(now())->toBeSudoSessionValue();
});

it('can be deactivated', function () {
    $this->sudo->activate();

    expect(session()->has(SudoSession::ConfirmedAt->value))->toBeTrue();

    $this->sudo->deactivate();

    expect(now())->not->toBeSudoSessionValue()
        ->and(session()->has(SudoSession::ConfirmedAt->value))->toBeFalse();
});

it('can determine if the sudo session is active', function () {
    $this->sudo->activate();
    expect($this->sudo->isActive())->toBeTrue();

    $this->travelTo(now()->addHours(2)->subSecond());
    expect($this->sudo->isActive())->toBeTrue();

    $this->travel(1)->second();
    expect($this->sudo->isActive())->toBeFalse();
});
