<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class ProfileFilamentEvent
{
    use Dispatchable;
    use SerializesModels;
}
