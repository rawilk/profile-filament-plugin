<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Rawilk\ProfileFilament\Enums\Session\SudoSession;

expect()->extend('toBeSudoSessionValue', function () {
    $lastConfirmed = Date::parse(
        session()->get(SudoSession::ConfirmedAt->value, 0)
    );

    return $this->toBe($lastConfirmed->toDateTimeString());
});

expect()->intercept('toBe', Model::class, function (Model $model) {
    expect($this->value->is($model))->toBeTrue();

    return $this;
});
