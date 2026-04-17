<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Contracts;

use Filament\Actions\Action;

interface RegistersDynamicActions
{
    public function registerDynamicAction(Action $action): void;

    public function getDynamicAction(string $name): ?Action;

    /**
     * @return array<string, Action>
     */
    public function getDynamicActions(): array;
}
