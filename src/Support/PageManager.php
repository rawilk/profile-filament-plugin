<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Support;

use Filament\Panel;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Rawilk\ProfileFilament\Features;
use Rawilk\ProfileFilament\Livewire as Components;

/** @deprecated */
final class PageManager
{
    private array $defaults;

    private Features $features;

    private Panel $panel;

    public function __construct()
    {
        $this->setPageDefaults();
    }

    public static function make(): self
    {
        return new self;
    }

    public function setPanel(Panel $panel): self
    {
        $this->panel = $panel;

        return $this;
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

    public function preparePages(): self
    {
        if (! $this->features->hasProfileForm()) {
            unset($this->defaults[\Rawilk\ProfileFilament\Filament\Pages\ProfileTemp\ProfileInfo::class]['components'][Components\Profile\ProfileInfo::class]);
        }

        if (! $this->features->hasUpdateEmail()) {
            unset($this->defaults[\Rawilk\ProfileFilament\Filament\Pages\ProfileTemp\Settings::class]['components'][Components\Emails\UserEmail::class]);
        }

        if (! $this->features->hasDeleteAccount()) {
            unset($this->defaults[\Rawilk\ProfileFilament\Filament\Pages\ProfileTemp\Settings::class]['components'][Components\DeleteAccount::class]);
        }

        if (! $this->features->hasUpdatePassword()) {
            unset($this->defaults[\Rawilk\ProfileFilament\Filament\Pages\ProfileTemp\Security::class]['components'][Components\UpdatePassword::class]);
        }

        if (! $this->features->hasPasskeys() || ! $this->features->hasWebauthn()) {
            unset($this->defaults[\Rawilk\ProfileFilament\Filament\Pages\ProfileTemp\Security::class]['components'][Components\PasskeyManager::class]);
        }

        if (! $this->features->hasTwoFactorAuthentication() && ! $this->features->hasPasskeys()) {
            unset($this->defaults[\Rawilk\ProfileFilament\Filament\Pages\ProfileTemp\Security::class]['components'][Components\MfaOverview::class]);
        }

        if (! $this->features->hasSessionManager()) {
            unset($this->defaults[\Rawilk\ProfileFilament\Filament\Pages\ProfileTemp\Sessions::class]['components'][Components\Sessions\SessionManager::class]);
        }

        return $this;
    }

    public function registerPages(): void
    {
        $panelId = $this->panel->getId();
        $enabledPages = $this->getEnabledPages();

        $enabledPages->each(function (string $implementation, string $pluginClass) use ($panelId) {
            if (method_exists($implementation, 'registerPanelSlug')) {
                $implementation::registerPanelSlug($panelId, $this->pageSlug($pluginClass));
            }
        });

        $this->panel->pages(
            $enabledPages->all()
        );
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

    private function getEnabledPages(): Collection
    {
        return collect([
            \Rawilk\ProfileFilament\Filament\Pages\ProfileTemp\ProfileInfo::class => $this->pageIsEnabled(\Rawilk\ProfileFilament\Filament\Pages\ProfileTemp\ProfileInfo::class),
            \Rawilk\ProfileFilament\Filament\Pages\ProfileTemp\Settings::class => $this->pageIsEnabled(\Rawilk\ProfileFilament\Filament\Pages\ProfileTemp\Settings::class),
            \Rawilk\ProfileFilament\Filament\Pages\ProfileTemp\Security::class => $this->pageIsEnabled(\Rawilk\ProfileFilament\Filament\Pages\ProfileTemp\Security::class),
            \Rawilk\ProfileFilament\Filament\Pages\ProfileTemp\Sessions::class => $this->pageIsEnabled(\Rawilk\ProfileFilament\Filament\Pages\ProfileTemp\Sessions::class),
        ])
            ->filter()
            ->map(fn (bool $enabled, string $className) => $this->pageClassName($className));
    }

    private function setPageDefaults(): void
    {
        $this->defaults = [
            \Rawilk\ProfileFilament\Filament\Pages\ProfileTemp\ProfileInfo::class => $this->defaultsFor(\Rawilk\ProfileFilament\Filament\Pages\ProfileTemp\ProfileInfo::class),
            \Rawilk\ProfileFilament\Filament\Pages\ProfileTemp\Settings::class => $this->defaultsFor(\Rawilk\ProfileFilament\Filament\Pages\ProfileTemp\Settings::class),
            \Rawilk\ProfileFilament\Filament\Pages\ProfileTemp\Security::class => $this->defaultsFor(\Rawilk\ProfileFilament\Filament\Pages\ProfileTemp\Security::class),
            \Rawilk\ProfileFilament\Filament\Pages\ProfileTemp\Sessions::class => $this->defaultsFor(\Rawilk\ProfileFilament\Filament\Pages\ProfileTemp\Sessions::class),

            ...$this->defaults ?? [],
        ];
    }

    private function defaultsFor(string $page): array
    {
        return match ($page) {
            \Rawilk\ProfileFilament\Filament\Pages\ProfileTemp\ProfileInfo::class => [
                'enabled' => true,
                'slug' => 'user',
                'icon' => 'heroicon-o-user',
                'className' => \Rawilk\ProfileFilament\Filament\Pages\ProfileTemp\ProfileInfo::class,
                'components' => [
                    Components\Profile\ProfileInfo::class => ['class' => Components\Profile\ProfileInfo::class, 'sort' => 0],
                ],
                'sort' => 0,
                'group' => null,
            ],

            \Rawilk\ProfileFilament\Filament\Pages\ProfileTemp\Settings::class => [
                'enabled' => true,
                'slug' => 'admin',
                'icon' => 'heroicon-o-cog-6-tooth',
                'className' => \Rawilk\ProfileFilament\Filament\Pages\ProfileTemp\Settings::class,
                'components' => [
                    Components\Emails\UserEmail::class => ['class' => Components\Emails\UserEmail::class, 'sort' => 0],
                    Components\DeleteAccount::class => ['class' => Components\DeleteAccount::class, 'sort' => 15],
                ],
                'sort' => 10,
                'group' => null,
            ],

            \Rawilk\ProfileFilament\Filament\Pages\ProfileTemp\Security::class => [
                'enabled' => true,
                'slug' => 'security',
                'icon' => 'heroicon-o-shield-exclamation',
                'className' => \Rawilk\ProfileFilament\Filament\Pages\ProfileTemp\Security::class,
                'sort' => 20,
                'components' => [
                    Components\UpdatePassword::class => ['class' => Components\UpdatePassword::class, 'sort' => 0],
                    Components\PasskeyManager::class => ['class' => Components\PasskeyManager::class, 'sort' => 15],
                    Components\MfaOverview::class => ['class' => Components\MfaOverview::class, 'sort' => 30],
                ],
                'group' => null,
            ],

            \Rawilk\ProfileFilament\Filament\Pages\ProfileTemp\Sessions::class => [
                'enabled' => true,
                'slug' => 'sessions',
                'icon' => 'heroicon-o-signal',
                'className' => \Rawilk\ProfileFilament\Filament\Pages\ProfileTemp\Sessions::class,
                'components' => [
                    Components\Sessions\SessionManager::class => ['class' => Components\Sessions\SessionManager::class, 'sort' => 0],
                ],
                'sort' => 30,
                'group' => null,
            ],

            default => throw new InvalidArgumentException("Page `{$page}` is not supported."),
        };
    }
}
