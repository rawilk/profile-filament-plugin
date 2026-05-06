<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Pages\Profile\PageConfiguration;

use BackedEnum;
use Filament\Pages\PageConfiguration;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

abstract class ProfilePageConfiguration extends PageConfiguration
{
    protected string|UnitEnum|null $navigationGroup = null;

    protected string|BackedEnum|null $navigationIcon = null;

    protected ?string $navigationLabel = null;

    protected ?int $navigationSort = null;

    protected bool $translateNavigationGroup = false;

    protected ?array $livewireComponents = null;

    protected bool $mergeLivewireComponents = false;

    protected null|string|Htmlable $title = null;

    abstract protected function getDefaultNavigationIcon(): string|BackedEnum|null;

    abstract protected function getDefaultNavigationLabel(): ?string;

    abstract protected function getDefaultNavigationSort(): ?int;

    abstract protected function getDefaultTitle(): ?string;

    public function components(?array $components = null, bool $merge = false): static
    {
        $this->livewireComponents = $components;
        $this->mergeLivewireComponents = $merge;

        return $this;
    }

    public function navigationGroup(string|UnitEnum|null $group, bool $translate = false): static
    {
        $this->navigationGroup = $group;
        $this->translateNavigationGroup = $translate;

        return $this;
    }

    public function navigationIcon(string|BackedEnum|null $icon): static
    {
        $this->navigationIcon = $icon;

        return $this;
    }

    public function navigationLabel(?string $label): static
    {
        $this->navigationLabel = $label;

        return $this;
    }

    public function navigationSort(?int $sort): static
    {
        $this->navigationSort = $sort;

        return $this;
    }

    public function title(null|string|Htmlable $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getLivewireComponents(): ?array
    {
        return $this->livewireComponents;
    }

    public function shouldMergeLivewireComponents(): bool
    {
        return $this->mergeLivewireComponents;
    }

    public function getNavigationGroup(): string|UnitEnum|null
    {
        $label = $this->navigationGroup;

        if ($this->translateNavigationGroup) {
            $label = __($label);
        }

        return $label;
    }

    public function getNavigationIcon(): string|BackedEnum|null
    {
        return $this->navigationIcon ?? $this->getDefaultNavigationIcon();
    }

    public function getNavigationSort(): ?int
    {
        return $this->navigationSort ?? $this->getDefaultNavigationSort();
    }

    public function getNavigationLabel(): ?string
    {
        return $this->navigationLabel ?? $this->getDefaultNavigationLabel();
    }

    public function getTitle(): null|string|Htmlable
    {
        return $this->title ?? $this->getDefaultTitle();
    }
}
