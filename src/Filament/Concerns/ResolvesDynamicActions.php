<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Concerns;

use Filament\Actions\Action;

trait ResolvesDynamicActions
{
    use InteractsWithDynamicActions;

    public function getAction(string|array $actions, bool $isMounting = true): ?Action
    {
        ray('getAction', $actions);
        $action = parent::getAction($actions, $isMounting);

        if ($action || is_array($actions)) {
            return $action;
        }

        return $this->getDynamicAction($actions);
    }
}
