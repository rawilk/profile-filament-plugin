<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Dto\Auth;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Http\Request;
use Illuminate\Support\Traits\Macroable;
use Rawilk\ProfileFilament\Enums\Livewire\MfaChallengeMode;

class TwoFactorLoginEventBag
{
    use Macroable;

    public function __construct(
        public User $user,
        public bool $remember,
        public array $data,
        public ?Request $request,
        public MfaChallengeMode $mfaChallengeMode,
        public ?array $assertionResponse = null,
    ) {}
}
