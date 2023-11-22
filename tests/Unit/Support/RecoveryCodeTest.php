<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Rawilk\ProfileFilament\Support\RecoveryCode;

it('can generate a recovery code', function () {
    Str::createRandomStringsUsing(fn (): string => 'my-random-string');

    expect(RecoveryCode::generate())->toBe('my-random-string-my-random-string');
});

it('can be provided a callback to generate recovery codes from', function () {
    RecoveryCode::generateCodesUsing(fn (): string => 'foo');

    expect(RecoveryCode::generate())->toBe('foo');
});
