<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Pages;

abstract class ProfilePageGroup
{
    public static function getSort(): int
    {
        return static::$sort ?? 99;
    }

    public static function isCollapsible(): bool
    {
        return (bool) (static::$collapsible ?? true);
    }

    public static function getIcon(): ?string
    {
        return static::$icon ?? null;
    }

    public static function getLabel(): ?string
    {
        return static::$label ?? class_basename(static::class);
    }

    public static function parentGroup(): ?string
    {
        return static::$parentGroupClass ?? null;
    }

    final public static function innerNavArrayKey(): string
    {
        $key = static::class;

        if ($parentKey = static::parentGroup()) {
            throw_unless(
                class_exists($parentKey) && is_subclass_of($parentKey, __CLASS__),
                "Parent group `{$parentKey}` must extend `" . __CLASS__ . '`',
            );

            $key = "{$parentKey}.{$key}";
        }

        return $key;
    }
}
