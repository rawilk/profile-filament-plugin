<?php

declare(strict_types=1);

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Rawilk\ProfileFilament\Enums\Session\SudoSession;
use Rawilk\ProfileFilament\Facades\Mfa;

// Add better handling for 'toBe' assertion for Eloquent models.
expect()->intercept('toBe', Model::class, function (Model $model) {
    return expect($this->value->is($model))->toBeTrue();
});

// Add better handling for 'toBe' assertion for Dates.
expect()->intercept('toBe', CarbonInterface::class, function (CarbonInterface $date) {
    return expect($date->equalTo($this->value))->toBeTrue(
        "Expected date [{$date}] does not equal actual date [{$this->value}]",
    );
});

expect()->extend('modelsMatchExactly', function (Collection $expectedModels) {
    expect($this->value->pluck('id')->toArray())
        ->toEqualCanonicalizing($expectedModels->pluck('id')->toArray());
});

expect()->extend('toBeQueryCount', function () {
    return $this->toBe(queryCount());
});

expect()->extend('toBeSudoSessionValue', function () {
    $lastConfirmed = Date::parse(
        session()->get(SudoSession::ConfirmedAt->value, 0),
    );

    return expect($this->value)->toBe($lastConfirmed);
});

expect()->extend('isMfaConfirmed', function () {
    return expect(Mfa::isConfirmedInSession($this->value))->toBeTrue(
        'User mfa session not confirmed',
    );
});

expect()->extend('toBePasswordFor', function (mixed $user) {
    return expect(Hash::check($this->value, $user->getAuthPassword()))->toBeTrue(
        "\"{$this->value}\" is not the password for the user (id: {$user->getKey()})"
    );
});
