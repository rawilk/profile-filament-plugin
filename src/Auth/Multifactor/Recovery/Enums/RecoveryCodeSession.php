<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Recovery\Enums;

enum RecoveryCodeSession: string
{
    case SettingUp = 'pf-recovery:setting-up';

    public function flash(mixed $value): void
    {
        session()->flash($this->value, $value);
    }

    public function missing(): bool
    {
        return session()->missing($this->value);
    }
}
