<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Plugin\Concerns;

use Filament\Pages\PageConfiguration;
use Filament\Panel;
use Rawilk\ProfileFilament\Filament\Pages\Profile;

trait HasProfilePages
{
    protected null|string|PageConfiguration $profileInfoPage = Profile\ProfileInfo::class;

    protected null|string|PageConfiguration $sessionsPage = Profile\Sessions::class;

    protected null|string|PageConfiguration $settingsPage = Profile\Settings::class;

    protected null|string|PageConfiguration $securityPage = Profile\Security::class;

    protected array $pageConfigurations = [];

    /**
     * A mapping of page classes. Helps resolve urls when custom pages are used.
     *
     * @var array<class-string, class-string>
     */
    protected array $pageClassMap = [];

    public function profileInfoPage(null|string|PageConfiguration $page = Profile\ProfileInfo::class): static
    {
        $this->profileInfoPage = $page;
        $this->setPageConfiguration($page);
        $this->mapPageClass(Profile\ProfileInfo::class, $page);

        return $this;
    }

    public function sessionsPage(null|string|PageConfiguration $page = Profile\Sessions::class): static
    {
        $this->sessionsPage = $page;
        $this->setPageConfiguration($page);
        $this->mapPageClass(Profile\Sessions::class, $page);

        return $this;
    }

    public function securityPage(null|string|PageConfiguration $page = Profile\Security::class): static
    {
        $this->securityPage = $page;
        $this->setPageConfiguration($page);
        $this->mapPageClass(Profile\Security::class, $page);

        return $this;
    }

    public function settingsPage(null|string|PageConfiguration $page = Profile\Settings::class): static
    {
        $this->settingsPage = $page;
        $this->setPageConfiguration($page);
        $this->mapPageClass(Profile\Settings::class, $page);

        return $this;
    }

    public function getProfileInfoPage(): null|string|PageConfiguration
    {
        return $this->profileInfoPage;
    }

    public function getSessionsPage(): null|string|PageConfiguration
    {
        return $this->sessionsPage;
    }

    public function getSecurityPage(): null|string|PageConfiguration
    {
        return $this->securityPage;
    }

    public function getSettingsPage(): null|string|PageConfiguration
    {
        return $this->settingsPage;
    }

    public function hasProfileInfoPage(): bool
    {
        return filled($this->getProfileInfoPage());
    }

    public function hasSessionsPage(): bool
    {
        return filled($this->getSessionsPage());
    }

    public function hasSecurityPage(): bool
    {
        return filled($this->getSecurityPage());
    }

    public function hasSettingsPage(): bool
    {
        return filled($this->getSettingsPage());
    }

    public function getPageConfiguration(string $pageClass): ?PageConfiguration
    {
        return $this->pageConfigurations[$pageClass] ?? null;
    }

    public function getPageUrl(string $internalClass): string
    {
        $page = $this->getCurrentPageClass($internalClass);

        /** @var class-string<\Filament\Pages\Page> $class */
        $class = $page instanceof PageConfiguration ? $page->getPage() : $page;
        $configKey = $page instanceof PageConfiguration ? $page->getKey() : null;

        return $class::getUrl(configuration: $configKey);
    }

    /**
     * @return class-string<\Filament\Pages\Page>|PageConfiguration
     */
    public function getCurrentPageClass(string $page): string|PageConfiguration
    {
        return $this->pageClassMap[$page] ?? $page;
    }

    protected function registerProfilePages(Panel $panel): void
    {
        $pages = [];

        if ($this->hasProfileInfoPage()) {
            $pages[] = $this->getProfileInfoPage();
        }

        if ($this->hasSessionsPage()) {
            $pages[] = $this->getSessionsPage();
        }

        if ($this->hasSettingsPage()) {
            $pages[] = $this->getSettingsPage();
        }

        if ($this->hasSecurityPage()) {
            $pages[] = $this->getSecurityPage();
        }

        $panel->pages($pages);
    }

    protected function mapPageClass(string $internalClass, null|string|PageConfiguration $page): void
    {
        if ($page === null) {
            return;
        }

        $this->pageClassMap[$internalClass] = $page;
    }

    protected function setPageConfiguration(null|string|PageConfiguration $configuration): void
    {
        if (! $configuration instanceof PageConfiguration) {
            return;
        }

        $this->pageConfigurations[$configuration->getPage()] = $configuration;
    }
}
