<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Tests\TestSupport\Services;

class RandomGeneratorFake
{
    public function token(): string
    {
        return 'fake-random-string';
    }
}
