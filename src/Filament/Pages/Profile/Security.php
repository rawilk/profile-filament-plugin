<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Pages\Profile;

use Illuminate\Contracts\Support\Htmlable;
use Rawilk\ProfileFilament\Concerns;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

class Security extends ProfilePage
{
    use Concerns\HasPanelPageRoutes;

    public static function getNavigationLabel(): string
    {
        return __('profile-filament::pages/security.title');
    }

    public function getTitle(): string|Htmlable
    {
        return __('profile-filament::pages/security.title');
    }

    protected function needsSudoChallengeForm(): bool
    {
        return filament(ProfileFilamentPlugin::PLUGIN_ID)->hasSudoMode();
    }
}
