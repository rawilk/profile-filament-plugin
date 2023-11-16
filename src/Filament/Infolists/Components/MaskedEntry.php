<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Infolists\Components;

use Closure;
use Filament\Infolists\Components\Concerns;
use Filament\Infolists\Components\Contracts\HasAffixActions;
use Filament\Infolists\Components\Entry;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Filament\Support\Concerns\CanBeCopied;
use Illuminate\Support\Str;

class MaskedEntry extends Entry implements HasAffixActions
{
    use CanBeCopied;
    use Concerns\HasAffixes;
    use Concerns\HasColor;
    use Concerns\HasFontFamily;
    use Concerns\HasWeight;

    protected ?Closure $maskUsingCallback = null;

    protected bool|Closure|null $isSudoRequired = false;

    protected TextEntrySize|string|Closure|null $size = null;

    protected string $view = 'profile-filament::filament.infolists.masked-entry';

    public function maskUsing(Closure $callback): static
    {
        $this->maskUsingCallback = $callback;

        return $this;
    }

    public function requireSudoConfirmation(bool|Closure|null $condition = true): static
    {
        $this->isSudoRequired = $condition;

        return $this;
    }

    public function size(TextEntrySize|string|Closure|null $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function maskedValue(): string
    {
        $value = $this->evaluate($this->maskUsingCallback);

        return $value ?? Str::mask($this->getState(), '*', 0);
    }

    public function isSudoRequired(): bool
    {
        return (bool) $this->evaluate($this->isSudoRequired);
    }

    public function getSize(mixed $state): TextEntrySize|string|null
    {
        return $this->evaluate($this->size, [
            'state' => $state,
        ]);
    }
}
