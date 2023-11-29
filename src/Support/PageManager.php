<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Rawilk\FilamentInnerNav\InnerNav;
use Rawilk\FilamentInnerNav\InnerNavGroup;
use Rawilk\FilamentInnerNav\InnerNavItem;
use Rawilk\ProfileFilament\Concerns\IsProfilePage;
use Rawilk\ProfileFilament\Exceptions\InvalidProfileNavigation;
use Rawilk\ProfileFilament\Features;
use Rawilk\ProfileFilament\Filament\Pages\Profile;
use Rawilk\ProfileFilament\Filament\Pages\ProfilePageGroup;
use Rawilk\ProfileFilament\Filament\Pages\Security;
use Rawilk\ProfileFilament\Filament\Pages\Sessions;
use Rawilk\ProfileFilament\Filament\Pages\Settings;
use Rawilk\ProfileFilament\Livewire\DeleteAccount;
use Rawilk\ProfileFilament\Livewire\Emails\UserEmail;
use Rawilk\ProfileFilament\Livewire\MfaOverview;
use Rawilk\ProfileFilament\Livewire\PasskeyManager;
use Rawilk\ProfileFilament\Livewire\Profile\ProfileInfo;
use Rawilk\ProfileFilament\Livewire\Sessions\SessionManager;
use Rawilk\ProfileFilament\Livewire\UpdatePassword;

final class PageManager
{
    private array $defaults;

    private array $pages;

    private array $customPages = [];

    private Features $features;

    public function __construct()
    {
        $this->setPageDefaults();
    }

    public static function make(): self
    {
        return new self;
    }

    public function withFeatures(Features $features): self
    {
        $this->features = $features;

        return $this;
    }

    public function pageIsEnabled(string $page): bool
    {
        return Arr::get($this->defaults, "{$page}.enabled", false);
    }

    public function pageClassName(string $page): string
    {
        return Arr::get($this->defaults, "{$page}.className", $page);
    }

    public function pageIcon(string $page): ?string
    {
        return Arr::get($this->defaults, "{$page}.icon");
    }

    public function pageSlug(string $page): string
    {
        return Arr::get($this->defaults, "{$page}.slug", '');
    }

    public function pageSort(string $page): int
    {
        return Arr::get($this->defaults, "{$page}.sort", 99);
    }

    public function pageGroup(string $page): ?string
    {
        return Arr::get($this->defaults, "{$page}.group");
    }

    public function preparePages(): array
    {
        $this->pages = [];

        if ($this->pageIsEnabled(Profile::class)) {
            $this->pages[Profile::class] = $this->pageClassName(Profile::class);

            if (! $this->features->hasProfileForm()) {
                unset($this->defaults[Profile::class]['components'][ProfileInfo::class]);
            }
        }

        if ($this->pageIsEnabled(Settings::class)) {
            $this->pages[Settings::class] = $this->pageClassName(Settings::class);

            if (! $this->features->hasUpdateEmail()) {
                unset($this->defaults[Settings::class]['components'][UserEmail::class]);
            }

            if (! $this->features->hasDeleteAccount()) {
                unset($this->defaults[Settings::class]['components'][DeleteAccount::class]);
            }
        }

        if ($this->pageIsEnabled(Security::class)) {
            $this->pages[Security::class] = $this->pageClassName(Security::class);

            if (! $this->features->hasUpdatePassword()) {
                unset($this->defaults[Security::class]['components'][UpdatePassword::class]);
            }

            if (! $this->features->hasPasskeys() || ! $this->features->hasWebauthn()) {
                unset($this->defaults[Security::class]['components'][PasskeyManager::class]);
            }

            if (! $this->features->hasTwoFactorAuthentication() && ! $this->features->hasPasskeys()) {
                unset($this->defaults[Security::class]['components'][MfaOverview::class]);
            }
        }

        if ($this->pageIsEnabled(Sessions::class)) {
            $this->pages[Sessions::class] = $this->pageClassName(Sessions::class);

            if (! $this->features->hasSessionManager()) {
                unset($this->defaults[Sessions::class]['components'][SessionManager::class]);
            }
        }

        return array_values($this->pages);
    }

    /**
     * @param  class-string<\Filament\Pages\Page>  $className
     */
    public function addPage(string $className): self
    {
        throw_unless(class_exists($className), "Class `{$className}` does not exist");

        $this->customPages[$className] = $className;

        return $this;
    }

    public function toInnerNav(): InnerNav
    {
        $groups = [
            '__none' => [],
        ];

        collect($this->pages)
            ->merge($this->customPages)
            ->map(fn (string $className) => app($className))
            ->filter(function ($page) {
                throw_unless(
                    in_array(IsProfilePage::class, class_uses_recursive($page::class), true),
                    InvalidProfileNavigation::invalidPage($page::class),
                );

                return $page::shouldRegisterInnerNav();
            })
            ->each(function ($page) use (&$groups) {
                $groupName = $page::innerNavGroup();

                $this->ensureGroupIsValid($groupName);

                if ($groupName === null) {
                    $groups['__none'][] = $page;

                    return;
                }

                /** @var \Rawilk\ProfileFilament\Filament\Pages\ProfilePageGroup $groupName */
                $arrayKey = $groupName::innerNavArrayKey();
                $groupItems = Arr::get($groups, $arrayKey, []);
                $groupItems[] = $page;

                Arr::set($groups, $arrayKey, $groupItems);
            });

        $pages = collect($groups)
            ->transform(function (array $pages, string $groupName) {
                $mappedPages = $this->mapToInnerNavItems(pages: collect($pages));

                if ($groupName === '__none') {
                    return $mappedPages;
                }

                return $this->mapToInnerNavGroup(groupName: $groupName, pages: $mappedPages);
            })
            ->flatten();

        return InnerNav::make()
            ->setNavigationItems($pages);
    }

    public function setDefaultsFor(
        string $page,
        ?bool $enabled,
        ?string $slug,
        ?string $icon,
        ?string $className,
        array $components,
        ?int $sort,
        ?string $group,
    ): void {
        $defaults = [];

        if (is_bool($enabled)) {
            $defaults['enabled'] = $enabled;
        }

        if (is_string($slug)) {
            $defaults['slug'] = $slug;
        }

        if (is_string($icon)) {
            $defaults['icon'] = $icon;
        }

        if (is_string($className) && class_exists($className)) {
            $defaults['className'] = $className;
        }

        if (is_int($sort)) {
            $defaults['sort'] = $sort;
        }

        if (is_string($group)) {
            $this->ensureGroupIsValid($group);

            $defaults['group'] = $group;
        }

        $pageDefaults = $this->defaultsFor($page);

        $this->defaults[$page] = [
            ...Arr::except($pageDefaults, 'components'),
            ...$defaults,
            'components' => [
                ...$pageDefaults['components'] ?? [],
                ...$components,
            ],
        ];
    }

    public function componentsFor(string $page): Collection
    {
        return collect(Arr::get($this->defaults, "{$page}.components", []))
            ->sortBy(function (string|array $componentDefinition) {
                if (is_string($componentDefinition)) {
                    return $componentDefinition::$sort ?? -1;
                }

                return $componentDefinition['sort'] ?? -1;
            })
            ->map(fn (string|array $componentDefinition) => is_array($componentDefinition) ? $componentDefinition['class'] : $componentDefinition);
    }

    public function swapComponent(string $page, string $component, string $newComponent): self
    {
        $componentDefinition = Arr::get($this->defaults, "{$page}.components.{$component}");

        $componentDefinition = is_array($componentDefinition)
            ? [...$componentDefinition, ...['class' => $newComponent]]
            : $newComponent;

        Arr::set($this->defaults, "{$page}.components.{$component}", $componentDefinition);

        return $this;
    }

    public function setComponentSort(string $page, string $component, int $sort): self
    {
        $componentDefinition = Arr::get($this->defaults, "{$page}.components.{$component}", []);
        if (! is_array($componentDefinition)) {
            $componentDefinition = ['class' => $componentDefinition];
        }

        $componentDefinition['sort'] = $sort;

        Arr::set($this->defaults, "{$page}.components.{$component}", $componentDefinition);

        return $this;
    }

    private function mapToInnerNavItems(Collection $pages, int $level = 0): Collection
    {
        return $pages->transform(function (mixed $page, string|int $index) use ($level) {
            if (is_array($page)) {
                throw_if($level > 0, InvalidProfileNavigation::tooManyNestedLevels());

                // Only collapsible groups are allowed to be nested.
                throw_unless($index::isCollapsible(), InvalidProfileNavigation::nestedStaticGroup($index));

                return $this->mapToInnerNavGroup(
                    groupName: $index,
                    pages: $this->mapToInnerNavItems(pages: collect($page), level: $level + 1)
                );
            }

            $url = $page::getUrl();

            return InnerNavItem::make()
                ->label($page::getNavigationLabel())
                ->url($url)
                ->sort($page::innerNavSort())
                ->icon($page::getNavigationIcon())
                ->isActiveWhen(fn () => request()?->url() === $url);
        });
    }

    /**
     * @param  class-string<\Rawilk\ProfileFilament\Filament\Pages\ProfilePageGroup>  $groupName
     */
    private function mapToInnerNavGroup(string $groupName, Collection $pages): InnerNavGroup
    {
        return InnerNavGroup::make($groupName)
            ->label($groupName::getLabel())
            ->icon($groupName::getIcon())
            ->sort($groupName::getSort())
            ->collapsible($groupName::isCollapsible())
            ->items($pages);
    }

    private function setPageDefaults(): void
    {
        $this->defaults = [
            Profile::class => $this->defaultsFor(Profile::class),
            Settings::class => $this->defaultsFor(Settings::class),
            Security::class => $this->defaultsFor(Security::class),
            Sessions::class => $this->defaultsFor(Sessions::class),

            ...$this->defaults ?? [],
        ];
    }

    private function defaultsFor(string $page): array
    {
        return match ($page) {
            Profile::class => [
                'enabled' => true,
                'slug' => 'profile',
                'icon' => 'heroicon-o-user',
                'className' => Profile::class,
                'components' => [
                    ProfileInfo::class => ['class' => ProfileInfo::class, 'sort' => 0],
                ],
                'sort' => 0,
                'group' => null,
            ],

            Settings::class => [
                'enabled' => true,
                'slug' => 'profile/admin',
                'icon' => 'heroicon-o-cog-6-tooth',
                'className' => Settings::class,
                'components' => [
                    UserEmail::class => ['class' => UserEmail::class, 'sort' => 0],
                    DeleteAccount::class => ['class' => DeleteAccount::class, 'sort' => 15],
                ],
                'sort' => 10,
                'group' => null,
            ],

            Security::class => [
                'enabled' => true,
                'slug' => 'profile/security',
                'icon' => 'heroicon-o-shield-exclamation',
                'className' => Security::class,
                'sort' => 20,
                'components' => [
                    UpdatePassword::class => ['class' => UpdatePassword::class, 'sort' => 0],
                    PasskeyManager::class => ['class' => PasskeyManager::class, 'sort' => 15],
                    MfaOverview::class => ['class' => MfaOverview::class, 'sort' => 30],
                ],
                'group' => null,
            ],

            Sessions::class => [
                'enabled' => true,
                'slug' => 'profile/sessions',
                'icon' => 'heroicon-o-signal',
                'className' => Sessions::class,
                'components' => [
                    SessionManager::class => ['class' => SessionManager::class, 'sort' => 0],
                ],
                'sort' => 30,
                'group' => null,
            ],

            default => throw new InvalidArgumentException("Page `{$page}` is not supported."),
        };
    }

    private function ensureGroupIsValid(?string $group): void
    {
        if (! $group) {
            return;
        }

        throw_unless(
            class_exists($group) && is_subclass_of($group, ProfilePageGroup::class),
            InvalidProfileNavigation::invalidGroup($group),
        );
    }
}
