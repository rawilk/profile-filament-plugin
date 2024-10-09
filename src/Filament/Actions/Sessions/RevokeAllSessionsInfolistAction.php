<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Actions\Sessions;

use Filament\Infolists\Components\Actions\Action;

class RevokeAllSessionsInfolistAction extends Action
{
    use Concerns\RevokesAllSessions;
}
