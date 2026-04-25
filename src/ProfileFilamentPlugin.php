<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Rawilk\ProfileFilament\Plugin as ProfilePlugin;

class ProfileFilamentPlugin implements Plugin
{
    use EvaluatesClosures;
    use ProfilePlugin\Concerns\AddsProfileMenuItem;
    use ProfilePlugin\Concerns\HasAuth;
    use ProfilePlugin\Concerns\HasEmailVerification;
    use ProfilePlugin\Concerns\HasMultiFactorAuth;
    use ProfilePlugin\Concerns\HasProfileCluster;
    use ProfilePlugin\Concerns\HasProfilePages;
    use ProfilePlugin\Concerns\HasSudoMode;
    use ProfilePlugin\Concerns\UpdatesUserPassword;

    public const string PLUGIN_ID = 'rawilk/profile-filament-plugin';

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
        $panel
            ->discoverClusters(in: __DIR__ . '/Filament/Clusters', for: 'Rawilk\\ProfileFilament\\Filament\\Clusters');

        $this->registerProfilePages($panel);
        $this->configureProfileMenuItem($panel);
    }

    public function boot(Panel $panel): void
    {
        if ($this->shouldAddPasskeyActionToLogin()) {
            FilamentView::registerRenderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn () => Blade::render('<x-profile-filament::passkey-login />'),
            );
        }
    }
}
