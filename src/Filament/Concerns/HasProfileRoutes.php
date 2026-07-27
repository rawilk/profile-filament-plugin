<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Concerns;

use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Database\Eloquent\Model;
use Rawilk\ProfileFilament\Facades\ProfileFilament;
use Rawilk\ProfileFilament\ProfileFilamentPlugin as ProfilePlugin;

trait HasProfileRoutes
{
    public static function getUrl(
        array $parameters = [],
        bool $isAbsolute = true,
        ?string $panel = null,
        ?Model $tenant = null,
        bool $shouldGuessMissingParameters = false,
        ?string $configuration = null,
    ): string {
        $resolvedPanel = filled($panel)
            ? Filament::getPanel($panel)
            : Filament::getCurrentOrDefaultPanel();

        if (static::profileRoutesAreTenantAware($resolvedPanel)) {
            return parent::getUrl(
                parameters: $parameters,
                isAbsolute: $isAbsolute,
                panel: $panel,
                tenant: $tenant,
                shouldGuessMissingParameters: $shouldGuessMissingParameters,
                configuration: $configuration,
            );
        }

        if (filled($configuration)) {
            return static::withConfiguration(
                $configuration,
                static fn (): string => static::getUrl(
                    parameters: $parameters,
                    isAbsolute: $isAbsolute,
                    panel: $panel,
                ),
            );
        }

        return route(
            static::getRouteName($resolvedPanel),
            $parameters,
            $isAbsolute,
        );
    }

    protected static function profileRoutesAreTenantAware(?Panel $panel = null): bool
    {
        $panel ??= Filament::getCurrentOrDefaultPanel();

        if (! $panel->hasPlugin(ProfilePlugin::PLUGIN_ID)) {
            return true;
        }

        return ProfileFilament::plugin($panel->getId())->isTenantAware();
    }
}
