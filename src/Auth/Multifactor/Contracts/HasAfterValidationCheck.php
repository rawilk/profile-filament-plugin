<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Contracts;

use Livewire\Component;

interface HasAfterValidationCheck
{
    public function afterValidationCheck(array $data, Component $livewire): void;
}
