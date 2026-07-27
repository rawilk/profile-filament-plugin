<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Plugin\Concerns;

use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Pages\PageConfiguration;
use Filament\Panel;
use Rawilk\ProfileFilament\Filament\Clusters\ProfileCluster;
use Rawilk\ProfileFilament\Filament\Pages\Profile;

trait HasProfilePages
{
    /** @var null|class-string<Page>|PageConfiguration */
    protected null|string|PageConfiguration $profileInfoPage = Profile\ProfileInfo::class;

    /** @var null|class-string<Page>|PageConfiguration */
    protected null|string|PageConfiguration $sessionsPage = Profile\Sessions::class;

    /** @var null|class-string<Page>|PageConfiguration */
    protected null|string|PageConfiguration $settingsPage = Profile\Settings::class;

    /** @var null|class-string<Page>|PageConfiguration */
    protected null|string|PageConfiguration $securityPage = Profile\Security::class;

    /** @var array<class-string<Page>, PageConfiguration> */
    protected array $pageConfigurations = [];

    /**
     * A mapping of page classes. Helps resolve urls when custom pages are used.
     *
     * @var array<class-string<Page>, class-string<Page>|PageConfiguration>
     */
    protected array $pageClassMap = [];

    /** @param null|class-string<Page>|PageConfiguration $page */
    public function profileInfoPage(null|string|PageConfiguration $page = Profile\ProfileInfo::class): static
    {
        $this->profileInfoPage = $page;
        $this->setPageConfiguration($page);
        $this->mapPageClass(Profile\ProfileInfo::class, $page);

        return $this;
    }

    /** @param null|class-string<Page>|PageConfiguration $page */
    public function sessionsPage(null|string|PageConfiguration $page = Profile\Sessions::class): static
    {
        $this->sessionsPage = $page;
        $this->setPageConfiguration($page);
        $this->mapPageClass(Profile\Sessions::class, $page);

        return $this;
    }

    /** @param null|class-string<Page>|PageConfiguration $page */
    public function securityPage(null|string|PageConfiguration $page = Profile\Security::class): static
    {
        $this->securityPage = $page;
        $this->setPageConfiguration($page);
        $this->mapPageClass(Profile\Security::class, $page);

        return $this;
    }

    /** @param null|class-string<Page>|PageConfiguration $page */
    public function settingsPage(null|string|PageConfiguration $page = Profile\Settings::class): static
    {
        $this->settingsPage = $page;
        $this->setPageConfiguration($page);
        $this->mapPageClass(Profile\Settings::class, $page);

        return $this;
    }

    /** @return null|class-string<Page>|PageConfiguration */
    public function getProfileInfoPage(): null|string|PageConfiguration
    {
        return $this->profileInfoPage;
    }

    /** @return null|class-string<Page>|PageConfiguration */
    public function getSessionsPage(): null|string|PageConfiguration
    {
        return $this->sessionsPage;
    }

    /** @return null|class-string<Page>|PageConfiguration */
    public function getSecurityPage(): null|string|PageConfiguration
    {
        return $this->securityPage;
    }

    /** @return null|class-string<Page>|PageConfiguration */
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

    /** @param class-string<Page> $internalClass */
    public function getPageUrl(string $internalClass): string
    {
        $page = $this->getCurrentPageClass($internalClass);

        /** @var class-string<Page> $class */
        $class = $page instanceof PageConfiguration ? $page->getPage() : $page;
        $configKey = $page instanceof PageConfiguration ? $page->getKey() : null;

        return $class::getUrl(configuration: $configKey);
    }

    /**
     * @param  class-string<Page>  $page
     * @return class-string<Page>|PageConfiguration
     */
    public function getCurrentPageClass(string $page): string|PageConfiguration
    {
        return $this->pageClassMap[$page] ?? $page;
    }

    /** @return array<class-string<Page>> */
    public function getProfilePageClasses(): array
    {
        return array_values(array_unique(array_map(
            static fn (string|PageConfiguration $page): string => $page instanceof PageConfiguration
                ? $page->getPage()
                : $page,
            $this->getProfilePages(),
        )));
    }

    protected function registerGlobalProfilePages(Panel $panel): void
    {
        $pages = $this->getProfilePages();

        $panel
            ->livewireComponents([
                ProfileCluster::class,
                ...$this->getProfilePageClasses(),
            ])
            ->authenticatedRoutes(function (Panel $panel) use ($pages): void {
                ProfileCluster::registerRoutes($panel);

                foreach ($pages as $page) {
                    $pageClass = $page instanceof PageConfiguration
                        ? $page->getPage()
                        : $page;
                    $configuration = $page instanceof PageConfiguration
                        ? $page
                        : null;

                    Filament::setCurrentPageConfigurationKey($configuration?->getKey());

                    try {
                        $pageClass::registerRoutes($panel, $configuration);
                    } finally {
                        Filament::setCurrentPageConfigurationKey(null);
                    }
                }
            });
    }

    protected function registerTenantAwareProfilePages(Panel $panel): void
    {
        $panel->discoverClusters(
            in: dirname(__DIR__, 2) . '/Filament/Clusters',
            for: 'Rawilk\\ProfileFilament\\Filament\\Clusters',
        );

        $panel->pages($this->getProfilePages());
    }

    /**
     * @param  class-string<Page>  $internalClass
     * @param  null|class-string<Page>|PageConfiguration  $page
     */
    protected function mapPageClass(string $internalClass, null|string|PageConfiguration $page): void
    {
        if ($page === null) {
            return;
        }

        $this->pageClassMap[$internalClass] = $page;
    }

    /** @param null|class-string<Page>|PageConfiguration $configuration */
    protected function setPageConfiguration(null|string|PageConfiguration $configuration): void
    {
        if (! $configuration instanceof PageConfiguration) {
            return;
        }

        $this->pageConfigurations[$configuration->getPage()] = $configuration;
    }

    /** @return array<class-string<Page>|PageConfiguration> */
    protected function getProfilePages(): array
    {
        $pages = [];

        foreach ([
            $this->getProfileInfoPage(),
            $this->getSessionsPage(),
            $this->getSettingsPage(),
            $this->getSecurityPage(),
        ] as $page) {
            if ($page !== null) {
                $pages[] = $page;
            }
        }

        return $pages;
    }
}
