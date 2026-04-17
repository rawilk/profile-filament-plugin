<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Pages\Profile\Concerns;

use BackedEnum;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

trait HasProfileConfigurations
{
    public static function getNavigationLabel(): string
    {
        if ($configuration = static::resolveConfiguration()) {
            if ($label = $configuration->getNavigationLabel()) {
                return $label;
            }
        }

        return static::getDefaultNavigationLabel() ?? parent::getNavigationLabel();
    }

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        if ($configuration = static::resolveConfiguration()) {
            if ($icon = $configuration->getNavigationIcon()) {
                return $icon;
            }
        }

        return static::getDefaultNavigationIcon() ?? parent::getNavigationIcon();
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        if ($configuration = static::resolveConfiguration()) {
            if ($group = $configuration->getNavigationGroup()) {
                return $group;
            }
        }

        return parent::getNavigationGroup();
    }

    public static function getNavigationSort(): ?int
    {
        if ($configuration = static::resolveConfiguration()) {
            $sort = $configuration->getNavigationSort();

            if (filled($sort)) {
                return $sort;
            }
        }

        return static::getDefaultNavigationSort() ?? parent::getNavigationSort();
    }

    public function getTitle(): string|Htmlable
    {
        if ($configuration = static::resolveConfiguration()) {
            return $configuration->getTitle();
        }

        return static::getDefaultTitle() ?? parent::getTitle();
    }

    /**
     * Fallback for when no configuration is present.
     */
    protected static function getDefaultNavigationIcon(): string|BackedEnum|null
    {
        return null;
    }

    /**
     * Fallback for when no configuration is present.
     */
    protected static function getDefaultNavigationLabel(): ?string
    {
        return null;
    }

    /**
     * Fallback for when no configuration is present.
     */
    protected static function getDefaultNavigationSort(): ?int
    {
        return null;
    }

    /**
     * Fallback for when no configuration is present.
     */
    protected static function getDefaultTitle(): null|string|Htmlable
    {
        return null;
    }
}
