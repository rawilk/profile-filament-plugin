<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Actions\Emails;

use Filament\Infolists\Components\Actions\Action;

class EditEmailAction extends Action
{
    use Concerns\EditsAuthenticatedUserEmail;
}
