<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Enums\Livewire;

enum SensitiveProfileSection: string
{
    case Email = 'email';
    case Mfa = 'mfa';
    case Passkeys = 'passkeys';
    case UpdatePassword = 'update-password';
}
