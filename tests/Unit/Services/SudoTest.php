<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Date;
use Rawilk\ProfileFilament\Enums\Session\SudoSession;
use Rawilk\ProfileFilament\Services\Sudo;

beforeEach(function () {
    $this->sudo = new Sudo(
        expiration: DateInterval::createFromDateString('2 hours'),
    );

    $this->startSession();

    Date::setTestNow('2023-01-01 10:00:00');
});

it('can be activated', function () {
    $this->sudo->activate();

    expect('2023-01-01 10:00:00')->toBeSudoSessionValue();
});

it('can be extended', function () {
    $this->sudo->activate();

    $this->travelTo(now()->addHour());

    $this->sudo->extend();

    expect('2023-01-01 11:00:00')->toBeSudoSessionValue();
});

it('can be deactivated', function () {
    $this->sudo->activate();

    expect(session()->has(SudoSession::ConfirmedAt->value))->toBeTrue();

    $this->sudo->deactivate();

    expect('2023-01-01 10:00:00')->not->toBeSudoSessionValue()
        ->and(session()->has(SudoSession::ConfirmedAt->value))->toBeFalse();
});

it('can determine if sudo session is active', function () {
    // 10:00:00
    $this->sudo->activate();
    expect($this->sudo->isActive())->toBeTrue();

    // 11:59:59
    $this->travelTo(now()->addHours(2)->subSecond());
    expect($this->sudo->isActive())->toBeTrue();

    // 12:00:00
    $this->travelTo(now()->addSecond());
    expect($this->sudo->isActive())->toBeFalse();
});
