<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Concerns;

use Filament\Actions\Action;

trait InteractsWithDynamicActions
{
    /** @var array<string, Action> */
    protected array $dynamicActions = [];

    public function registerDynamicAction(Action $action): void
    {
        $this->dynamicActions[$action->getName()] = $action;
    }

    public function getDynamicAction(string $name): ?Action
    {
        return $this->dynamicActions[$name] ?? null;
    }

    public function getDynamicActions(): array
    {
        return $this->dynamicActions;
    }
}
