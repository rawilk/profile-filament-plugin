<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Actions\Emails;

use Filament\Actions\Action;

class CancelPendingEmailChangeAction extends Action
{
    use Concerns\CancelsPendingEmailChanges;
}
