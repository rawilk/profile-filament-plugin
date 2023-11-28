<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Concerns;

use Rawilk\ProfileFilament\ProfileFilamentPlugin;

trait IsProfilePage
{
    /** @final This method should not be overridden in most cases. */
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function shouldRegisterInnerNav(): bool
    {
        return true;
    }

    public static function innerNavGroup(): ?string
    {
        return null;
    }

    public static function innerNavSort(): int
    {
        return static::$innerNavSort ?? 99;
    }

    public function getBreadcrumbs(): array
    {
        $rootPage = filament(ProfileFilamentPlugin::PLUGIN_ID)->getRootProfilePage();

        $breadcrumb = $this->isRootProfilePage() ? null : $this->getBreadcrumb();

        return [
            ...(filled($rootPage) ? [$rootPage::getUrl() => app($rootPage)->getBreadcrumb()] : []),
            ...(filled($breadcrumb) ? [$breadcrumb] : []),
        ];
    }

    public function getBreadcrumb(): string
    {
        return static::$breadcrumb ?? static::getNavigationLabel();
    }

    protected function isRootProfilePage(): bool
    {
        return filament(ProfileFilamentPlugin::PLUGIN_ID)->isRootProfilePage(static::class);
    }
}
