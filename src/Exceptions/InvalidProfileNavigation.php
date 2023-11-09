<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Exceptions;

use Rawilk\ProfileFilament\Concerns\IsProfilePage;
use Rawilk\ProfileFilament\Filament\Pages\ProfilePageGroup;
use RuntimeException;

final class InvalidProfileNavigation extends RuntimeException
{
    public static function tooManyNestedLevels(): self
    {
        return new self('Only one nested level of navigation groups are supported at this time.');
    }

    public static function invalidGroup(string $group): self
    {
        return new self("Group `{$group}` must extend `" . ProfilePageGroup::class . '`');
    }

    public static function invalidPage(string $page): self
    {
        return new self('The `' . IsProfilePage::class . "` trait must be used in `{$page}`");
    }

    public static function nestedStaticGroup(string $group): self
    {
        return new self("The nested group `{$group}` is set as not collapsible. Only collapsible groups are allowed to be nested.");
    }
}
