<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Dto\SudoChallengeAssertions;

use Livewire\Wireable;

/** @deprecated */
class SudoChallengeAssertion implements Wireable
{
    public function __construct(
        protected bool $isValid,
        protected ?string $error = null,
    ) {
    }

    public static function make(bool $isValid, ?string $error = null): static
    {
        return new static($isValid, $error);
    }

    public static function fromLivewire($value): static
    {
        return new static(
            $value['isValid'] ?? true,
            $value['error'] ?? null,
        );
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function toLivewire(): array
    {
        return [
            'isValid' => $this->isValid,
            'error' => $this->error,
        ];
    }
}
