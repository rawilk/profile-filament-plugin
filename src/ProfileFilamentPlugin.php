<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Illuminate\Support\Collection;
use Rawilk\ProfileFilament\Filament\Clusters\Profile;
use Rawilk\ProfileFilament\Filament\Pages\MfaChallenge;
use Rawilk\ProfileFilament\Filament\Pages\Profile\ProfileInfo;
use Rawilk\ProfileFilament\Filament\Pages\SudoChallenge;
use Rawilk\ProfileFilament\Http\Middleware\RequiresTwoFactorAuthentication;
use Rawilk\ProfileFilament\Support\PageManager;

class ProfileFilamentPlugin implements Plugin
{
    public const PLUGIN_ID = 'rawilk/profile-filament-plugin';

    protected array $defaults;

    protected bool $showInUserMenu = true;

    protected string $userMenuIcon = 'heroicon-o-cog-6-tooth';

    protected string $rootProfilePage = ProfileInfo::class;

    /**
     * The root slug of the profile pages cluster.
     */
    protected string $clusterSlug = 'profile';

    protected ?Features $features = null;

    protected PageManager $pageManager;

    protected null|string|Closure|array $mfaChallengeAction = null;

    protected null|string|Closure|array $sudoChallengeAction = null;

    protected bool $mfaMiddlewareEnabled = true;

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        return filament(app(static::class)->getId());
    }

    public function getId(): string
    {
        return static::PLUGIN_ID;
    }

    public function register(Panel $panel): void
    {
        if (! $this->features) {
            $this->features = Features::defaults();
        }

        $panelId = $panel->getId();

        Profile::registerPanelSlug($panelId, $this->getClusterSlug());

        $this
            ->pageManager()
            ->setPanel($panel)
            ->withFeatures($this->features)
            ->preparePages()
            ->registerPages();

        $panel
            ->discoverClusters(in: __DIR__ . '/Filament/Clusters', for: 'Rawilk\\ProfileFilament\\Filament\\Clusters');

        if ($this->showInUserMenu && $this->isEnabled($this->rootProfilePage)) {
            $panel->userMenuItems([
                'profile' => MenuItem::make()
                    ->label(fn () => __('profile-filament::pages/profile.user_menu_label'))
                    ->icon($this->userMenuIcon)
                    ->url(fn () => $this->pageUrl($this->rootProfilePage)),
            ]);
        }

        if ($this->features->hasTwoFactorAuthentication()) {
            $this->mfaMiddlewareEnabled && $panel->authMiddleware([
                RequiresTwoFactorAuthentication::class,
            ]);
        }
    }

    public function boot(Panel $panel): void
    {
    }

    public function features(Features $features): self
    {
        $this->features = $features;

        return $this;
    }

    public function hideFromUserMenu(): self
    {
        $this->showInUserMenu = false;

        return $this;
    }

    public function usingUserMenuIcon(string $icon): self
    {
        $this->userMenuIcon = $icon;

        return $this;
    }

    public function usingClusterSlug(string $slug): self
    {
        $this->clusterSlug = $slug;

        return $this;
    }

    public function usingRootProfilePage(string $page): self
    {
        $this->rootProfilePage = $page;

        return $this;
    }

    public function challengeMfaWith(string|Closure|array|null $action = null): self
    {
        $this->mfaChallengeAction = $action;

        return $this;
    }

    public function getMfaChallengeAction(): string|Closure|array
    {
        return $this->mfaChallengeAction ?? MfaChallenge::class;
    }

    public function challengeSudoWith(string|Closure|array|null $action = null): self
    {
        $this->sudoChallengeAction = $action;

        return $this;
    }

    public function getSudoChallengeAction(): string|Closure|array
    {
        return $this->sudoChallengeAction ?? SudoChallenge::class;
    }

    public function hasSudoMode(): bool
    {
        return $this->panelFeatures()->hasSudoMode();
    }

    public function getRootProfilePage(): string
    {
        return $this->rootProfilePage;
    }

    public function getClusterSlug(): string
    {
        return $this->clusterSlug;
    }

    public function isRootProfilePage(string $page): bool
    {
        return $this->rootProfilePage === $page;
    }

    public function useMfaMiddleware(bool $condition = true): self
    {
        $this->mfaMiddlewareEnabled = $condition;

        return $this;
    }

    public function profile(
        ?bool $enabled = null,
        ?string $slug = null,
        ?string $icon = null,
        ?string $className = null,
        array $components = [],
        ?int $sort = null,
        ?string $group = null,
    ): self {
        $this->pageManager()->setDefaultsFor(
            ProfileInfo::class,
            $enabled,
            $slug,
            $icon,
            $className,
            $components,
            $sort,
            $group,
        );

        return $this;
    }

    public function accountSecurity(
        ?bool $enabled = null,
        ?string $slug = null,
        ?string $icon = null,
        ?string $className = null,
        array $components = [],
        ?int $sort = null,
        ?string $group = null,
    ): self {
        $this->pageManager()->setDefaultsFor(
            Filament\Pages\Profile\Security::class,
            $enabled,
            $slug,
            $icon,
            $className,
            $components,
            $sort,
            $group,
        );

        return $this;
    }

    public function accountSettings(
        ?bool $enabled = null,
        ?string $slug = null,
        ?string $icon = null,
        ?string $className = null,
        array $components = [],
        ?int $sort = null,
        ?string $group = null,
    ): self {
        $this->pageManager()->setDefaultsFor(
            Filament\Pages\Profile\Settings::class,
            $enabled,
            $slug,
            $icon,
            $className,
            $components,
            $sort,
            $group,
        );

        return $this;
    }

    public function sessions(
        ?bool $enabled = null,
        ?string $slug = null,
        ?string $icon = null,
        ?string $className = null,
        array $components = [],
        ?int $sort = null,
        ?string $group = null,
    ): self {
        $this->pageManager()->setDefaultsFor(
            Filament\Pages\Profile\Sessions::class,
            $enabled,
            $slug,
            $icon,
            $className,
            $components,
            $sort,
            $group,
        );

        return $this;
    }

    public function getSlug(string $page): string
    {
        return $this->pageManager()->pageSlug($page);
    }

    public function getPageRouteName(string $page): string
    {
        return (string) str($this->getSlug($page))->replace('/', '.');
    }

    public function getIcon(string $page): ?string
    {
        return $this->pageManager()->pageIcon($page);
    }

    public function pageUrl(string $page): string
    {
        /** @var class-string<\Filament\Pages\Page> $className */
        $className = $this->pageManager()->pageClassName($page);

        return $className::getUrl(panel: filament()->getId());
    }

    public function isEnabled(string $page): bool
    {
        return $this->pageManager()->pageIsEnabled($page);
    }

    public function pageSort(string $page): int
    {
        return $this->pageManager()->pageSort($page);
    }

    public function pageGroup(string $page): ?string
    {
        return $this->pageManager()->pageGroup($page);
    }

    public function componentsFor(string $page): Collection
    {
        return $this->pageManager()->componentsFor($page);
    }

    public function setComponentSort(string $page, string $component, int $sort): self
    {
        $this->pageManager()->setComponentSort($page, $component, $sort);

        return $this;
    }

    public function swapComponent(string $page, string $component, string $newComponent): self
    {
        $this->pageManager()->swapComponent($page, $component, $newComponent);

        return $this;
    }

    public function panelFeatures(): Features
    {
        return $this->features;
    }

    public function pageManager(): PageManager
    {
        return $this->pageManager ?? $this->pageManager = PageManager::make();
    }
}
